<?php

namespace App\Controllers;

use App\Models\ClientModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class Reports extends BaseController
{
    private const REPORTS_PER_PAGE = 50;

    public function credits(): string
    {
        $sort = $this->resolveCreditSort();

        return view('reports/credits/index', $this->buildCreditsReportData($sort, true));
    }

    public function creditsPrint()
    {
        $sort = $this->resolveCreditSort();
        $html = view('reports/credits/print', $this->buildCreditsReportData($sort, false));

        return $this->renderPdf($html, 'credits-report.pdf', 'portrait');
    }

    public function overdue(): string
    {
        [$fromDueDate, $toDueDate] = $this->resolveDueDateRange();
        $drNo = $this->resolveDrNoFilter();
        $dueSort = $this->resolveDueSort();

        return view('reports/overdue/index', $this->buildOverdueReportData($fromDueDate, $toDueDate, $drNo, $dueSort, true));
    }

    public function overduePrint()
    {
        [$fromDueDate, $toDueDate] = $this->resolveDueDateRange();
        $drNo = $this->resolveDrNoFilter();
        $dueSort = $this->resolveDueSort();
        $html = view('reports/overdue/print', $this->buildOverdueReportData($fromDueDate, $toDueDate, $drNo, $dueSort, false));

        return $this->renderPdf($html, 'overdue-report.pdf', 'landscape');
    }

    private function buildCreditsReportData(string $sort, bool $paginate): array
    {
        $clientModel = new ClientModel();
        $clients = $clientModel->orderBy('name', 'asc')->findAll();
        $clientIds = array_map(static fn (array $client): int => (int) $client['id'], $clients);
        $balancesByClient = [];

        if (! empty($clientIds)) {
            $ledgerRows = db_connect()->table('ledger l')
                ->select('l.client_id, l.balance')
                ->whereIn('l.client_id', $clientIds)
                ->orderBy('l.client_id', 'asc')
                ->orderBy('l.entry_date', 'desc')
                ->orderBy('l.id', 'desc')
                ->get()
                ->getResultArray();

            foreach ($ledgerRows as $row) {
                $clientId = (int) ($row['client_id'] ?? 0);
                if ($clientId > 0 && ! array_key_exists($clientId, $balancesByClient)) {
                    $balancesByClient[$clientId] = (float) ($row['balance'] ?? 0);
                }
            }
        }

        $rows = [];
        $totalBalance = 0.0;

        foreach ($clients as $client) {
            $clientId = (int) ($client['id'] ?? 0);
            $creditLimit = (float) ($client['credit_limit'] ?? 0);
            $currentBalance = $balancesByClient[$clientId] ?? 0.0;
            $availableBalance = $creditLimit - $currentBalance;

            $rows[] = [
                'client_name' => $client['name'] ?? '',
                'credit_limit' => $creditLimit,
                'current_balance' => $currentBalance,
                'available_balance' => $availableBalance,
            ];

            $totalBalance += $currentBalance;
        }

        usort($rows, static function (array $left, array $right) use ($sort): int {
            $comparison = $left['available_balance'] <=> $right['available_balance'];

            if ($comparison === 0) {
                return strcasecmp($left['client_name'], $right['client_name']);
            }

            return $sort === 'desc' ? -$comparison : $comparison;
        });

        $pagedRows = $rows;
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = count($rows);
        $totalPages = max(1, (int) ceil($totalRows / self::REPORTS_PER_PAGE));

        if ($paginate) {
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * self::REPORTS_PER_PAGE;
            $pagedRows = array_slice($rows, $offset, self::REPORTS_PER_PAGE);
        } else {
            $currentPage = 1;
            $totalPages = 1;
        }

        return [
            'rows' => $pagedRows,
            'sort' => $sort,
            'allRowsCount' => $totalRows,
            'currentPage' => $currentPage,
            'perPage' => self::REPORTS_PER_PAGE,
            'totalPages' => $totalPages,
            'totalBalance' => $totalBalance,
        ];
    }

    private function buildOverdueReportData(string $fromDueDate, string $toDueDate, string $drNo, string $dueSort, bool $paginate): array
    {
        $asOf = date('Y-m-d');
        $db = db_connect();

        $builder = $db->table('deliveries d')
            ->select('c.name as client_name, d.dr_no, d.date, d.due_date, d.total_amount as amount')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(d.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('clients c', 'c.id = d.client_id', 'left')
            ->join('payment_allocations pa', 'pa.delivery_id = d.id', 'left')
            ->join('payments p', 'p.id = pa.payment_id', 'left')
            ->where('d.voided_at', null)
            ->where('d.due_date IS NOT NULL', null, false)
            ->where('d.due_date <', $asOf);

        if ($fromDueDate !== '') {
            $builder->where('d.due_date >=', $fromDueDate);
        }

        if ($toDueDate !== '') {
            $builder->where('d.due_date <=', $toDueDate);
        }

        if ($drNo !== '') {
            $builder->like('d.dr_no', $drNo);
        }

        $rows = $builder
            ->groupBy('d.id')
            ->having('balance >', 0)
            ->orderBy('d.due_date', $dueSort)
            ->orderBy('c.name', 'asc')
            ->orderBy('d.dr_no', 'asc')
            ->get()
            ->getResultArray();

        $totalAmount = 0.0;
        $totalBalance = 0.0;

        foreach ($rows as $row) {
            $totalAmount += (float) ($row['amount'] ?? 0);
            $totalBalance += (float) ($row['balance'] ?? 0);
        }

        $pagedRows = $rows;
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = count($rows);
        $totalPages = max(1, (int) ceil($totalRows / self::REPORTS_PER_PAGE));

        if ($paginate) {
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * self::REPORTS_PER_PAGE;
            $pagedRows = array_slice($rows, $offset, self::REPORTS_PER_PAGE);
        } else {
            $currentPage = 1;
            $totalPages = 1;
        }

        return [
            'asOf' => $asOf,
            'fromDueDate' => $fromDueDate,
            'toDueDate' => $toDueDate,
            'drNo' => $drNo,
            'dueSort' => $dueSort,
            'rows' => $pagedRows,
            'allRowsCount' => $totalRows,
            'currentPage' => $currentPage,
            'perPage' => self::REPORTS_PER_PAGE,
            'totalPages' => $totalPages,
            'totalAmount' => $totalAmount,
            'totalBalance' => $totalBalance,
        ];
    }

    private function resolveCreditSort(): string
    {
        return strtolower((string) $this->request->getGet('sort')) === 'desc' ? 'desc' : 'asc';
    }

    private function resolveDueDateRange(): array
    {
        $fromDueDate = trim((string) ($this->request->getGet('from_due_date') ?? ''));
        $toDueDate = trim((string) ($this->request->getGet('to_due_date') ?? ''));

        if ($fromDueDate !== '' && $toDueDate !== '' && $fromDueDate > $toDueDate) {
            [$fromDueDate, $toDueDate] = [$toDueDate, $fromDueDate];
        }

        return [$fromDueDate, $toDueDate];
    }

    private function resolveDrNoFilter(): string
    {
        return trim((string) ($this->request->getGet('dr_no') ?? ''));
    }

    private function resolveDueSort(): string
    {
        return strtolower((string) $this->request->getGet('due_sort')) === 'desc' ? 'desc' : 'asc';
    }

    private function renderPdf(string $html, string $filename, string $orientation)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }
}
