<?php

namespace App\Controllers;

use App\Models\PayableLedgerModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\SupplierModel;
use App\Models\SupplierOrderItemModel;
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

    private function buildReportData(int $supplierId, string $start, string $end, bool $paginate): array
    {
        $supplierModel = new SupplierModel();
        $selectedSupplier = $supplierId > 0 ? $supplierModel->find($supplierId) : null;
        $openingBalance = 0.0;
        $openingOpenBalance = 0.0;
        $rows = [];
        $itemsByPurchaseOrder = [];
        $itemCounts = [];
        $allocationsByPurchaseOrder = [];
        $allocationsByPayable = [];
        $otherAccountsByPayable = [];
        $payablesById = [];
        $supplierOrdersById = [];
        $supplierOrderItemsByOrder = [];
        $supplierOrderConsumptionsByOrder = [];
        $currentBalance = 0.0;
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = 0;
        $totalPages = 1;
        $rowOffset = 0;

        if ($selectedSupplier) {
            $ledgerModel = new PayableLedgerModel();
            $openingRows = [];

            if ($start !== '') {
                $openingRow = $ledgerModel
                    ->select('balance')
                    ->where('supplier_id', $supplierId)
                    ->where('entry_date <', $start)
                    ->orderBy('entry_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                $openingBalance = (float) ($openingRow['balance'] ?? 0);

                $openingRows = $ledgerModel
                    ->select('id, entry_date, qty, account_title, supplier_order_id, supplier_order_item_id, purchase_order_id')
                    ->where('supplier_id', $supplierId)
                    ->where('entry_date <', $start)
                    ->orderBy('entry_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->findAll();
            }

            $builder = $ledgerModel->where('supplier_id', $supplierId);
            if ($start !== '') {
                $builder->where('entry_date >=', $start);
            }
            if ($end !== '') {
                $builder->where('entry_date <=', $end);
            }

            $allRows = $builder->orderBy('entry_date', 'asc')->orderBy('id', 'asc')->findAll();
            $rows = $allRows;
            $totalRows = count($allRows);
            $totalPages = max(1, (int) ceil($totalRows / self::LEDGER_PER_PAGE));
            $currentBalance = $openingBalance;

            if (! empty($allRows)) {
                $lastRow = end($allRows);
                $currentBalance = (float) ($lastRow['balance'] ?? $openingBalance);
            }

            $supplierOrderQtyById = $this->fetchSupplierOrderQuantities(
                $this->collectSupplierOrderIdsFromLedgerRows($openingRows, $allRows)
            );
            $openingOpenBalance = $this->computeOpenBalanceFromLedgerRows($openingRows, $supplierOrderQtyById);
            $allRows = $this->applyOpenBalancesToRows($allRows, $openingOpenBalance, $supplierOrderQtyById);
            $rows = $allRows;

            if ($paginate) {
                $currentPage = min($currentPage, $totalPages);
                $rowOffset = ($currentPage - 1) * self::LEDGER_PER_PAGE;
                $rows = array_slice($allRows, $rowOffset, self::LEDGER_PER_PAGE);
            } else {
                $currentPage = 1;
                $totalPages = 1;
            }

            $purchaseOrderIds = array_filter(array_column($rows, 'purchase_order_id'));
            $payableIds = array_filter(array_column($rows, 'payable_id'));
            $supplierOrderIds = array_filter(array_column($rows, 'supplier_order_id'));
            $db = db_connect();

            if (! empty($purchaseOrderIds)) {
                $items = (new PurchaseOrderItemModel())
                    ->select('purchase_order_items.*, products.product_name')
                    ->select('supplier_orders.id as supplier_order_id, supplier_orders.po_no as supplier_order_po_no')
                    ->select('supplier_order_items.qty_balance as current_po_qty_balance')
                    ->join('products', 'products.id = purchase_order_items.product_id', 'left')
                    ->join('supplier_order_items', 'supplier_order_items.id = purchase_order_items.supplier_order_item_id', 'left')
                    ->join('supplier_orders', 'supplier_orders.id = supplier_order_items.supplier_order_id', 'left')
                    ->whereIn('purchase_order_id', $purchaseOrderIds)
                    ->orderBy('purchase_order_id', 'asc')
                    ->orderBy('id', 'asc')
                    ->findAll();

                foreach ($items as $item) {
                    $purchaseOrderId = (int) $item['purchase_order_id'];
                    $itemsByPurchaseOrder[$purchaseOrderId][] = $item;
                    $itemCounts[$purchaseOrderId] = ($itemCounts[$purchaseOrderId] ?? 0) + 1;
                    if (! empty($item['supplier_order_id'])) {
                        $supplierOrderIds[] = (int) $item['supplier_order_id'];
                    }
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

            $supplierOrderIds = array_values(array_unique(array_filter(array_map('intval', $supplierOrderIds))));
            if (! empty($supplierOrderIds)) {
                $supplierOrderRows = $db->table('supplier_orders so')
                    ->select('so.*, suppliers.name as supplier_name')
                    ->join('suppliers', 'suppliers.id = so.supplier_id', 'left')
                    ->whereIn('so.id', $supplierOrderIds)
                    ->get()
                    ->getResultArray();

                foreach ($supplierOrderRows as $row) {
                    $supplierOrdersById[(int) $row['id']] = $row;
                }

                $supplierOrderItems = (new SupplierOrderItemModel())
                    ->select('supplier_order_items.*, products.product_name')
                    ->join('products', 'products.id = supplier_order_items.product_id', 'left')
                    ->whereIn('supplier_order_id', $supplierOrderIds)
                    ->orderBy('supplier_order_id', 'asc')
                    ->orderBy('id', 'asc')
                    ->findAll();

                foreach ($supplierOrderItems as $item) {
                    $supplierOrderItemsByOrder[(int) $item['supplier_order_id']][] = $item;
                }

                $supplierOrderConsumptions = $db->table('purchase_order_items poi')
                    ->select('soi.supplier_order_id')
                    ->select('po.id as purchase_order_id, po.po_no as rr_no, po.date as rr_date')
                    ->select('p.product_name, poi.qty, poi.unit_price, poi.line_total, poi.po_qty_balance_after')
                    ->join('supplier_order_items soi', 'soi.id = poi.supplier_order_item_id', 'left')
                    ->join('purchase_orders po', 'po.id = poi.purchase_order_id', 'left')
                    ->join('products p', 'p.id = poi.product_id', 'left')
                    ->whereIn('soi.supplier_order_id', $supplierOrderIds)
                    ->where('po.voided_at', null)
                    ->orderBy('po.date', 'asc')
                    ->orderBy('po.id', 'asc')
                    ->orderBy('poi.id', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($supplierOrderConsumptions as $consumption) {
                    $supplierOrderConsumptionsByOrder[(int) $consumption['supplier_order_id']][] = $consumption;
                }

                foreach ($rows as $index => $row) {
                    $supplierOrderId = (int) ($row['supplier_order_id'] ?? 0);
                    if ($supplierOrderId > 0 && isset($supplierOrdersById[$supplierOrderId])) {
                        $rows[$index]['supplier_order_po_no'] = $supplierOrdersById[$supplierOrderId]['po_no'] ?? '';
                    }
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
            'openingOpenBalance' => $openingOpenBalance,
            'currentBalance' => $currentBalance,
            'rows' => $rows,
            'allRowsCount' => $totalRows,
            'currentPage' => $currentPage,
            'perPage' => self::LEDGER_PER_PAGE,
            'totalPages' => $totalPages,
            'rowOffset' => $rowOffset,
            'itemsByPurchaseOrder' => $itemsByPurchaseOrder,
            'itemCounts' => $itemCounts,
            'allocationsByPurchaseOrder' => $allocationsByPurchaseOrder,
            'allocationsByPayable' => $allocationsByPayable,
            'otherAccountsByPayable' => $otherAccountsByPayable,
            'payablesById' => $payablesById,
            'supplierOrdersById' => $supplierOrdersById,
            'supplierOrderItemsByOrder' => $supplierOrderItemsByOrder,
            'supplierOrderConsumptionsByOrder' => $supplierOrderConsumptionsByOrder,
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

    private function collectSupplierOrderIdsFromLedgerRows(array $openingRows, array $rows): array
    {
        $ids = [];
        foreach ([$openingRows, $rows] as $group) {
            foreach ($group as $row) {
                $supplierOrderId = (int) ($row['supplier_order_id'] ?? 0);
                if ($supplierOrderId > 0) {
                    $ids[$supplierOrderId] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private function fetchSupplierOrderQuantities(array $supplierOrderIds): array
    {
        $supplierOrderIds = array_values(array_unique(array_filter(array_map('intval', $supplierOrderIds))));
        if (empty($supplierOrderIds)) {
            return [];
        }

        $rows = db_connect()->table('supplier_order_items')
            ->select('supplier_order_id, SUM(qty_ordered) as qty_ordered', false)
            ->whereIn('supplier_order_id', $supplierOrderIds)
            ->groupBy('supplier_order_id')
            ->get()
            ->getResultArray();

        $qtyById = [];
        foreach ($rows as $row) {
            $supplierOrderId = (int) ($row['supplier_order_id'] ?? 0);
            if ($supplierOrderId > 0) {
                $qtyById[$supplierOrderId] = (float) ($row['qty_ordered'] ?? 0);
            }
        }

        return $qtyById;
    }

    private function computeOpenBalanceFromLedgerRows(array $rows, array $supplierOrderQtyById): float
    {
        $balance = 0.0;
        foreach ($rows as $row) {
            $balance += $this->openBalanceDelta($row, $supplierOrderQtyById);
        }

        return $balance;
    }

    private function applyOpenBalancesToRows(array $rows, float $startingBalance, array $supplierOrderQtyById): array
    {
        $balance = $startingBalance;
        foreach ($rows as $index => $row) {
            $balance += $this->openBalanceDelta($row, $supplierOrderQtyById);
            $rows[$index]['total_open_balance'] = $balance;
        }

        return $rows;
    }

    private function openBalanceDelta(array $row, array $supplierOrderQtyById): float
    {
        $accountTitle = (string) ($row['account_title'] ?? '');
        if ($accountTitle === 'Supplier PO') {
            return (float) ($row['qty'] ?? 0);
        }

        if ($accountTitle === 'Voided Supplier PO') {
            $supplierOrderId = (int) ($row['supplier_order_id'] ?? 0);
            return $supplierOrderId > 0 ? -1.0 * (float) ($supplierOrderQtyById[$supplierOrderId] ?? 0) : 0.0;
        }

        $purchaseOrderId = (int) ($row['purchase_order_id'] ?? 0);
        $hasSupplierOrderLink = ! empty($row['supplier_order_item_id']) || ! empty($row['supplier_order_id']);
        if ($purchaseOrderId > 0 && $accountTitle === '' && $hasSupplierOrderLink) {
            return -1.0 * (float) ($row['qty'] ?? 0);
        }

        return 0.0;
    }
}
