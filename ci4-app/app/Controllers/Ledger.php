<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\DeliveryItemModel;
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

    private function buildReportData(int $clientId, string $start, string $end, bool $paginate): array
    {
        $clientModel = new ClientModel();
        $clients = $clientModel->orderBy('name', 'asc')->findAll();

        $selectedClient = null;
        $openingBalance = 0.0;
        $rows = [];
        $itemsByDelivery = [];
        $itemCounts = [];
        $allocationsByDelivery = [];
        $allocationsByPayment = [];
        $otherAccountsByPayment = [];
        $paymentsById = [];
        $allRows = [];
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = 0;
        $totalPages = 1;
        $rowOffset = 0;

        if ($clientId > 0) {
            $selectedClient = $clientModel->find($clientId);
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

                $openingBalance = (float) ($openingRow['balance'] ?? 0);
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

            if ($paginate) {
                $currentPage = min($currentPage, $totalPages);
                $rowOffset = ($currentPage - 1) * self::LEDGER_PER_PAGE;
                $rows = array_slice($allRows, $rowOffset, self::LEDGER_PER_PAGE);
            } else {
                $currentPage = 1;
                $totalPages = 1;
            }

            $deliveryIds = array_filter(array_column($rows, 'delivery_id'));
            $paymentIds = array_filter(array_column($rows, 'payment_id'));

            if (! empty($deliveryIds)) {
                $itemModel = new DeliveryItemModel();
                $items = $itemModel
                    ->select('delivery_items.*, products.product_name')
                    ->join('products', 'products.id = delivery_items.product_id', 'left')
                    ->whereIn('delivery_id', $deliveryIds)
                    ->orderBy('delivery_id', 'asc')
                    ->orderBy('id', 'asc')
                    ->findAll();

                foreach ($items as $item) {
                    $deliveryId = (int) $item['delivery_id'];
                    $itemsByDelivery[$deliveryId][] = $item;
                    $itemCounts[$deliveryId] = ($itemCounts[$deliveryId] ?? 0) + 1;
                }
            }

            if (! empty($deliveryIds)) {
                $db = db_connect();
                $deliveryAllocations = $db->table('payment_allocations pa')
                    ->select('pa.delivery_id, pa.amount, p.pr_no, p.date')
                    ->join('payments p', 'p.id = pa.payment_id', 'left')
                    ->whereIn('pa.delivery_id', $deliveryIds)
                    ->orderBy('p.date', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($deliveryAllocations as $allocation) {
                    $deliveryId = (int) $allocation['delivery_id'];
                    $allocationsByDelivery[$deliveryId][] = $allocation;
                }
            }

            if (! empty($paymentIds)) {
                $db = db_connect();
                $paymentRows = $db->table('payments p')
                    ->select('p.id, p.pr_no, p.date, p.amount_received, p.amount_allocated, p.client_id')
                    ->whereIn('p.id', $paymentIds)
                    ->orderBy('p.date', 'asc')
                    ->orderBy('p.id', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($paymentRows as $row) {
                    $paymentId = (int) ($row['id'] ?? 0);
                    if ($paymentId <= 0) {
                        continue;
                    }

                    $paymentsById[$paymentId] = $row;
                }

                $paymentAllocations = $db->table('payment_allocations pa')
                    ->select('pa.payment_id, pa.amount, d.dr_no, d.date')
                    ->join('deliveries d', 'd.id = pa.delivery_id', 'left')
                    ->whereIn('pa.payment_id', $paymentIds)
                    ->orderBy('d.date', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($paymentAllocations as $allocation) {
                    $paymentId = (int) $allocation['payment_id'];
                    $allocationsByPayment[$paymentId][] = $allocation;
                }

                $otherAccountRows = $db->table('boa b')
                    ->select('b.payment_id, b.account_title, b.dr, b.ar_others, b.description, b.date, b.reference')
                    ->whereIn('b.payment_id', $paymentIds)
                    ->groupStart()
                        ->where('b.account_title IS NOT NULL', null, false)
                        ->orWhere('b.ar_others >', 0)
                    ->groupEnd()
                    ->orderBy('b.date', 'asc')
                    ->orderBy('b.id', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($otherAccountRows as $row) {
                    $paymentId = (int) $row['payment_id'];
                    $otherAccountsByPayment[$paymentId][] = $row;
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
            'rows' => $rows,
            'allRowsCount' => $totalRows,
            'currentPage' => $currentPage,
            'perPage' => self::LEDGER_PER_PAGE,
            'totalPages' => $totalPages,
            'rowOffset' => $rowOffset,
            'itemsByDelivery' => $itemsByDelivery,
            'itemCounts' => $itemCounts,
            'allocationsByDelivery' => $allocationsByDelivery,
            'allocationsByPayment' => $allocationsByPayment,
            'otherAccountsByPayment' => $otherAccountsByPayment,
            'paymentsById' => $paymentsById,
        ];
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
