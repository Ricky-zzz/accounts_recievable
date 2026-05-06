<?php

namespace App\Controllers;

use App\Models\PayableLedgerModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\SupplierModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class PayableLedger extends BaseController
{
    private const LEDGER_PER_PAGE = 20;

    public function index(): string
    {
        $supplierId = (int) $this->request->getGet('supplier_id');
        [$start, $end] = $this->resolveDateRange();

        return view('payable_ledger/index', $this->buildReportData($supplierId, $start, $end, true));
    }

    public function print()
    {
        $supplierId = (int) $this->request->getGet('supplier_id');
        [$start, $end] = $this->resolveDateRange();
        $data = $this->buildReportData($supplierId, $start, $end, false);

        if (! $data['selectedSupplier']) {
            return redirect()->to(base_url('payable-ledger'));
        }

        $payablesTotal = 0.0;
        $paymentTotal = 0.0;
        $otherAccountsTotal = 0.0;
        $endingBalance = (float) ($data['openingBalance'] ?? 0);

        foreach ($data['rows'] as $row) {
            $payablesTotal += (float) ($row['payables'] ?? 0);
            $paymentTotal += (float) ($row['payment'] ?? 0);
            $otherAccountsTotal += (float) ($row['other_accounts'] ?? 0);
            $endingBalance = (float) ($row['balance'] ?? $endingBalance);
        }

        $data['totals'] = [
            'payables' => $payablesTotal,
            'payment' => $paymentTotal,
            'other_accounts' => $otherAccountsTotal,
            'ending_balance' => $endingBalance,
        ];

        $html = view('payable_ledger/print', $data);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="payable-ledger-report.pdf"')
            ->setBody($dompdf->output());
    }

    public function saveForwardBalance()
    {
        $supplierId = (int) $this->request->getPost('supplier_id');
        $balanceInput = trim((string) $this->request->getPost('forwarded_balance'));

        if ($supplierId <= 0) {
            return redirect()->back()->with('error', 'Select a supplier first.');
        }

        $supplierModel = new SupplierModel();
        $supplier = $supplierModel->find($supplierId);

        if (! $supplier) {
            return redirect()->back()->with('error', 'Supplier not found.');
        }

        if ($balanceInput === '') {
            $balance = 0.0;
        } elseif (! is_numeric($balanceInput)) {
            return redirect()->back()->with('error', 'Forwarded balance must be a valid number.');
        } else {
            $balance = (float) $balanceInput;
        }

        $supplierModel->update($supplierId, ['forwarded_balance' => $balance]);
        $this->recalculateSupplierLedgerBalances($supplierId, $balance);

        return redirect()->back()->with('success', 'Forwarded balance saved.');
    }

    private function buildReportData(int $supplierId, string $start, string $end, bool $paginate): array
    {
        $supplierModel = new SupplierModel();
        $selectedSupplier = $supplierId > 0 ? $supplierModel->find($supplierId) : null;
        $openingBalance = 0.0;
        $forwardedBalance = 0.0;
        $currentBalance = 0.0;
        $rows = [];
        $itemsByPurchaseOrder = [];
        $allocationsByPurchaseOrder = [];
        $allocationsByPayable = [];
        $otherAccountsByPayable = [];
        $payablesById = [];
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = 0;
        $totalPages = 1;
        $rowOffset = 0;

        if ($selectedSupplier) {
            $forwardedBalance = (float) ($selectedSupplier['forwarded_balance'] ?? 0);
            if ($start !== '') {
                $openingRow = $this->filteredLedgerModel($supplierId)
                    ->select('balance')
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

            $builder = $this->filteredLedgerModel($supplierId);
            if ($start !== '') {
                $builder->where('entry_date >=', $start);
            }
            if ($end !== '') {
                $builder->where('entry_date <=', $end);
            }

            $allRows = $builder->orderBy('entry_date', 'asc')->orderBy('id', 'asc')->findAll();
            $totalRows = count($allRows);
            $totalPages = max(1, (int) ceil($totalRows / self::LEDGER_PER_PAGE));
            $currentBalance = $openingBalance;

            if (! empty($allRows)) {
                $lastRow = end($allRows);
                $currentBalance = (float) ($lastRow['balance'] ?? $openingBalance);
            }

            if ($paginate) {
                $currentPage = min($currentPage, $totalPages);
                $rowOffset = ($currentPage - 1) * self::LEDGER_PER_PAGE;
                $rows = array_slice($allRows, $rowOffset, self::LEDGER_PER_PAGE);
            } else {
                $rows = $allRows;
                $currentPage = 1;
                $totalPages = 1;
            }

            $purchaseOrderIds = array_filter(array_column($rows, 'purchase_order_id'));
            $payableIds = array_filter(array_column($rows, 'payable_id'));
            $db = db_connect();

            if (! empty($purchaseOrderIds)) {
                $items = (new PurchaseOrderItemModel())
                    ->select('purchase_order_items.*, products.product_name')
                    ->join('products', 'products.id = purchase_order_items.product_id', 'left')
                    ->whereIn('purchase_order_id', $purchaseOrderIds)
                    ->orderBy('purchase_order_id', 'asc')
                    ->orderBy('id', 'asc')
                    ->findAll();

                foreach ($items as $item) {
                    $itemsByPurchaseOrder[(int) $item['purchase_order_id']][] = $item;
                }

                $poAllocations = $db->table('payable_allocations pa')
                    ->select('pa.purchase_order_id, pa.amount, p.pr_no, p.date')
                    ->join('payables p', 'p.id = pa.payable_id', 'left')
                    ->whereIn('pa.purchase_order_id', $purchaseOrderIds)
                    ->orderBy('p.date', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($poAllocations as $allocation) {
                    $allocationsByPurchaseOrder[(int) $allocation['purchase_order_id']][] = $allocation;
                }
            }

            if (! empty($payableIds)) {
                $payableRows = $db->table('payables p')
                    ->select('p.id, p.pr_no, p.date, p.amount_received, p.amount_allocated, p.supplier_id')
                    ->whereIn('p.id', $payableIds)
                    ->orderBy('p.date', 'asc')
                    ->orderBy('p.id', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($payableRows as $row) {
                    $payableId = (int) ($row['id'] ?? 0);
                    if ($payableId > 0) {
                        $payablesById[$payableId] = $row;
                    }
                }

                $payableAllocations = $db->table('payable_allocations pa')
                    ->select('pa.payable_id, pa.amount, po.po_no, po.date')
                    ->join('purchase_orders po', 'po.id = pa.purchase_order_id', 'left')
                    ->whereIn('pa.payable_id', $payableIds)
                    ->orderBy('po.date', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($payableAllocations as $allocation) {
                    $allocationsByPayable[(int) $allocation['payable_id']][] = $allocation;
                }

                $otherRows = $db->table('payable_ledger pl')
                    ->select('pl.payable_id, pl.account_title, pl.other_accounts, pl.entry_date, pl.pr_no')
                    ->whereIn('pl.payable_id', $payableIds)
                    ->where('pl.account_title IS NOT NULL', null, false)
                    ->where('pl.other_accounts >', 0)
                    ->orderBy('pl.entry_date', 'asc')
                    ->orderBy('pl.id', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($otherRows as $row) {
                    $otherAccountsByPayable[(int) $row['payable_id']][] = $row;
                }
            }
        }

        return [
            'selectedSupplier' => $selectedSupplier,
            'supplierId' => $supplierId,
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
            'itemsByPurchaseOrder' => $itemsByPurchaseOrder,
            'allocationsByPurchaseOrder' => $allocationsByPurchaseOrder,
            'allocationsByPayable' => $allocationsByPayable,
            'otherAccountsByPayable' => $otherAccountsByPayable,
            'payablesById' => $payablesById,
        ];
    }

    private function recalculateSupplierLedgerBalances(int $supplierId, float $startingBalance): void
    {
        $ledgerModel = new PayableLedgerModel();
        $rows = $ledgerModel
            ->where('supplier_id', $supplierId)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->findAll();

        $balance = $startingBalance;
        foreach ($rows as $row) {
            $balance += (float) ($row['payables'] ?? 0);
            $balance -= (float) ($row['payment'] ?? 0);
            $balance -= (float) ($row['other_accounts'] ?? 0);

            if (abs((float) ($row['balance'] ?? 0) - $balance) > 0.005) {
                $ledgerModel->update((int) $row['id'], ['balance' => $balance]);
            }
        }
    }

    private function filteredLedgerModel(int $supplierId): PayableLedgerModel
    {
        return (new PayableLedgerModel())
            ->where('supplier_id', $supplierId)
            ->groupStart()
            ->where('account_title', null)
            ->orWhere("account_title NOT IN ('Supplier PO', 'Voided Supplier PO')", null, false)
            ->groupEnd();
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
