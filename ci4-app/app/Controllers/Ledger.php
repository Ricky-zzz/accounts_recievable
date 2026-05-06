<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\LedgerModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class Ledger extends BaseController
{
    private const LEDGER_PER_PAGE = 20;

    public function index(): string
    {
        $clientId = (int) $this->request->getGet('client_id');
        [$start, $end] = $this->resolveDateRange();

        $data = $this->buildReportData($clientId, $start, $end, true);
        return view('ledger/index', $data);
    }

    public function print()
    {
        $clientId = (int) $this->request->getGet('client_id');
        [$start, $end] = $this->resolveDateRange();

        $data = $this->buildReportData($clientId, $start, $end, false);

        if (! $data['selectedClient']) {
            return redirect()->to(base_url('ledger'));
        }

        $amountTotal = 0.0;
        $collectionTotal = 0.0;
        $otherAccountsTotal = 0.0;
        $endingBalance = (float) ($data['openingBalance'] ?? 0);

        foreach ($data['rows'] as $row) {
            $amountTotal += (float) ($row['amount'] ?? 0);
            $collectionTotal += (float) ($row['collection'] ?? 0);
            $otherAccountsTotal += (float) ($row['other_accounts'] ?? 0);
            $endingBalance = (float) ($row['balance'] ?? $endingBalance);
        }

        $data['totals'] = [
            'amount' => $amountTotal,
            'collection' => $collectionTotal,
            'other_accounts' => $otherAccountsTotal,
            'ending_balance' => $endingBalance,
        ];

        $html = view('ledger/print', $data);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="ledger-report.pdf"')
            ->setBody($dompdf->output());
    }

    public function saveForwardBalance()
    {
        $clientId = (int) $this->request->getPost('client_id');
        $balanceInput = trim((string) $this->request->getPost('forwarded_balance'));

        if ($clientId <= 0) {
            return redirect()->back()->with('error', 'Select a client first.');
        }

        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);

        if (! $client) {
            return redirect()->back()->with('error', 'Client not found.');
        }

        if ($balanceInput === '') {
            $balance = 0.0;
        } elseif (! is_numeric($balanceInput)) {
            return redirect()->back()->with('error', 'Forwarded balance must be a valid number.');
        } else {
            $balance = (float) $balanceInput;
        }

        $clientModel->update($clientId, ['forwarded_balance' => $balance]);
        $this->recalculateClientLedgerBalances($clientId, $balance);

        return redirect()->back()->with('success', 'Forwarded balance saved.');
    }

    private function buildReportData(int $clientId, string $start, string $end, bool $paginate): array
    {
        $clientModel = new ClientModel();
        $clients = $clientModel->orderBy('name', 'asc')->findAll();

        $selectedClient = null;
        $openingBalance = 0.0;
        $forwardedBalance = 0.0;
        $rows = [];
        $itemCounts = [];
        $allRows = [];
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = 0;
        $totalPages = 1;
        $rowOffset = 0;
        $currentBalance = 0.0;

        if ($clientId > 0) {
            $selectedClient = $clientModel->find($clientId);
            if ($selectedClient) {
                $forwardedBalance = (float) ($selectedClient['forwarded_balance'] ?? 0);
            }
        }

        if ($selectedClient) {
            $ledgerModel = new LedgerModel();

            if ($start !== '') {
                $openingRow = $ledgerModel
                    ->select('balance')
                    ->where('client_id', $clientId)
                    ->where('entry_date <', $start)
                    ->orderBy('entry_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($openingRow) {
                    $openingBalance = (float) ($openingRow['balance'] ?? 0);
                } else {
                    $openingBalance = $forwardedBalance;
                }
            } else {
                $openingBalance = $forwardedBalance;
            }

            $builder = $ledgerModel->where('client_id', $clientId);

            if ($start !== '') {
                $builder->where('entry_date >=', $start);
            }

            if ($end !== '') {
                $builder->where('entry_date <=', $end);
            }

            $allRows = $builder
                ->orderBy('entry_date', 'asc')
                ->orderBy('id', 'asc')
                ->findAll();

            $rows = $allRows;
            $totalRows = count($allRows);
            $totalPages = max(1, (int) ceil($totalRows / self::LEDGER_PER_PAGE));

            $currentBalance = $openingBalance;
            if (! empty($allRows)) {
                $lastRow = $allRows[array_key_last($allRows)] ?? null;
                if (is_array($lastRow) && array_key_exists('balance', $lastRow)) {
                    $currentBalance = (float) ($lastRow['balance'] ?? $openingBalance);
                }
            }

            if ($paginate) {
                $currentPage = min($currentPage, $totalPages);
                $rowOffset = ($currentPage - 1) * self::LEDGER_PER_PAGE;
                $rows = array_slice($allRows, $rowOffset, self::LEDGER_PER_PAGE);
            } else {
                $currentPage = 1;
                $totalPages = 1;
            }

            $deliveryIds = array_filter(array_map('intval', array_column($rows, 'delivery_id')));

            if (! empty($deliveryIds)) {
                $db = db_connect();
                $itemCountRows = $db->table('delivery_items')
                    ->select('delivery_id, COUNT(*) as item_count')
                    ->whereIn('delivery_id', $deliveryIds)
                    ->groupBy('delivery_id')
                    ->get()
                    ->getResultArray();

                foreach ($itemCountRows as $row) {
                    $itemCounts[(int) ($row['delivery_id'] ?? 0)] = (int) ($row['item_count'] ?? 0);
                }
            }
        }

        return [
            'clients' => $clients,
            'selectedClient' => $selectedClient,
            'clientId' => $clientId,
            'start' => $start,
            'end' => $end,
            'openingBalance' => $openingBalance,
            'forwardedBalance' => $forwardedBalance,
            'currentBalance' => $currentBalance,
            'rows' => $rows,
            'allRowsCount' => $totalRows,
            'currentPage' => $currentPage,
            'perPage' => self::LEDGER_PER_PAGE,
            'totalPages' => $totalPages,
            'rowOffset' => $rowOffset,
            'itemCounts' => $itemCounts,
        ];
    }

    private function recalculateClientLedgerBalances(int $clientId, float $startingBalance): void
    {
        $ledgerModel = new LedgerModel();
        $rows = $ledgerModel
            ->where('client_id', $clientId)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->findAll();

        $balance = $startingBalance;
        foreach ($rows as $row) {
            $balance += (float) ($row['amount'] ?? 0);
            $balance -= (float) ($row['collection'] ?? 0);
            $balance -= (float) ($row['other_accounts'] ?? 0);

            if (abs((float) ($row['balance'] ?? 0) - $balance) > 0.005) {
                $ledgerModel->update((int) $row['id'], ['balance' => $balance]);
            }
        }
    }

    private function resolveDateRange(): array
    {
        $start = trim((string) ($this->request->getGet('start') ?? ''));
        $end = trim((string) ($this->request->getGet('end') ?? ''));

        if ($start === '') {
            $start = date('Y-m-01');
        }

        if ($end === '') {
            $end = date('Y-m-t');
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
