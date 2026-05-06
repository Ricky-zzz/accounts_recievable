<?php

namespace App\Controllers;

use App\Models\PurchaseOrderHistoryModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\SupplierModel;
use App\Models\SupplierOrderHistoryModel;
use App\Models\SupplierOrderItemModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class PayableReports extends BaseController
{
    private const REPORTS_PER_PAGE = 50;
    private const VOIDED_PER_PAGE = 20;

    public function credits(): string
    {
        $sort = $this->resolveCreditSort();

        return view('payable_reports/credits/index', $this->buildCreditsReportData($sort, true));
    }

    public function creditsPrint()
    {
        $sort = $this->resolveCreditSort();
        $html = view('payable_reports/credits/print', $this->buildCreditsReportData($sort, false));

        return $this->renderPdf($html, 'supplier-credits-report.pdf', 'landscape');
    }

    public function overdue(): string
    {
        [$fromDueDate, $toDueDate] = $this->resolveDueDateRange();
        $poNo = $this->resolvePoNoFilter();
        $dueSort = $this->resolveDueSort();

        return view('payable_reports/overdue/index', $this->buildOverdueReportData($fromDueDate, $toDueDate, $poNo, $dueSort, true));
    }

    public function overduePrint()
    {
        [$fromDueDate, $toDueDate] = $this->resolveDueDateRange();
        $poNo = $this->resolvePoNoFilter();
        $dueSort = $this->resolveDueSort();
        $html = view('payable_reports/overdue/print', $this->buildOverdueReportData($fromDueDate, $toDueDate, $poNo, $dueSort, false));

        return $this->renderPdf($html, 'overdue-purchase-orders-report.pdf', 'landscape');
    }

    public function voided(): string
    {
        [$fromVoidedDate, $toVoidedDate] = $this->resolveVoidedDateRange();
        $poNo = $this->resolvePoNoFilter();

        return view('payable_reports/voided/index', $this->buildVoidedReportData($fromVoidedDate, $toVoidedDate, $poNo, true));
    }

    public function voidedPrint()
    {
        [$fromVoidedDate, $toVoidedDate] = $this->resolveVoidedDateRange();
        $poNo = $this->resolvePoNoFilter();
        $html = view('payable_reports/voided/print', $this->buildVoidedReportData($fromVoidedDate, $toVoidedDate, $poNo, false));

        return $this->renderPdf($html, 'voided-pickups-report.pdf', 'landscape');
    }

    public function voidedPos(): string
    {
        [$fromVoidedDate, $toVoidedDate] = $this->resolveVoidedDateRange();
        $poNo = $this->resolvePoNoFilter();

        return view('payable_reports/voided_pos/index', $this->buildVoidedPosReportData($fromVoidedDate, $toVoidedDate, $poNo, true));
    }

    public function voidedPosPrint()
    {
        [$fromVoidedDate, $toVoidedDate] = $this->resolveVoidedDateRange();
        $poNo = $this->resolvePoNoFilter();
        $html = view('payable_reports/voided_pos/print', $this->buildVoidedPosReportData($fromVoidedDate, $toVoidedDate, $poNo, false));

        return $this->renderPdf($html, 'voided-purchase-orders-report.pdf', 'landscape');
    }

    private function buildCreditsReportData(string $sort, bool $paginate): array
    {
        $suppliers = (new SupplierModel())->orderBy('name', 'asc')->findAll();
        $supplierIds = array_map(static fn(array $supplier): int => (int) $supplier['id'], $suppliers);
        $balancesBySupplier = [];

        if (! empty($supplierIds)) {
            $ledgerRows = db_connect()->table('payable_ledger pl')
                ->select('pl.supplier_id, pl.balance')
                ->whereIn('pl.supplier_id', $supplierIds)
                ->orderBy('pl.supplier_id', 'asc')
                ->orderBy('pl.entry_date', 'desc')
                ->orderBy('pl.id', 'desc')
                ->get()
                ->getResultArray();

            foreach ($ledgerRows as $row) {
                $supplierId = (int) ($row['supplier_id'] ?? 0);
                if ($supplierId > 0 && ! array_key_exists($supplierId, $balancesBySupplier)) {
                    $balancesBySupplier[$supplierId] = (float) ($row['balance'] ?? 0);
                }
            }
        }

        $rows = [];
        $totalBalance = 0.0;

        foreach ($suppliers as $supplier) {
            $supplierId = (int) ($supplier['id'] ?? 0);
            $creditLimit = (float) ($supplier['credit_limit'] ?? 0);
            $currentBalance = array_key_exists($supplierId, $balancesBySupplier)
                ? $balancesBySupplier[$supplierId]
                : (float) ($supplier['forwarded_balance'] ?? 0);
            $availableBalance = $creditLimit - $currentBalance;

            $rows[] = [
                'supplier_name' => $supplier['name'] ?? '',
                'credit_limit' => $creditLimit,
                'current_balance' => $currentBalance,
                'available_balance' => $availableBalance,
            ];

            $totalBalance += $currentBalance;
        }

        usort($rows, static function (array $left, array $right) use ($sort): int {
            $comparison = $left['available_balance'] <=> $right['available_balance'];
            if ($comparison === 0) {
                return strcasecmp($left['supplier_name'], $right['supplier_name']);
            }

            return $sort === 'desc' ? -$comparison : $comparison;
        });

        return $this->paginateRows($rows, self::REPORTS_PER_PAGE, $paginate) + [
            'sort' => $sort,
            'totalBalance' => $totalBalance,
        ];
    }

    private function buildOverdueReportData(string $fromDueDate, string $toDueDate, string $poNo, string $dueSort, bool $paginate): array
    {
        $asOf = date('Y-m-d');
        $db = db_connect();

        $builder = $db->table('purchase_orders po')
            ->select('s.name as supplier_name, po.po_no, po.date, po.due_date, po.total_amount as amount')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(po.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('suppliers s', 's.id = po.supplier_id', 'left')
            ->join('payable_allocations pa', 'pa.purchase_order_id = po.id', 'left')
            ->join('payables p', 'p.id = pa.payable_id', 'left')
            ->where('po.voided_at', null)
            ->where('po.due_date IS NOT NULL', null, false)
            ->where('po.due_date <', $asOf);

        if ($poNo === '' && $fromDueDate !== '') {
            $builder->where('po.due_date >=', $fromDueDate);
        }

        if ($poNo === '' && $toDueDate !== '') {
            $builder->where('po.due_date <=', $toDueDate);
        }

        if ($poNo !== '') {
            $builder->like('po.po_no', $poNo);
        }

        $rows = $builder
            ->groupBy('po.id')
            ->having('balance >', 0)
            ->orderBy('po.due_date', $dueSort)
            ->orderBy('s.name', 'asc')
            ->orderBy('po.po_no', 'asc')
            ->get()
            ->getResultArray();

        $totalAmount = 0.0;
        $totalBalance = 0.0;
        foreach ($rows as $row) {
            $totalAmount += (float) ($row['amount'] ?? 0);
            $totalBalance += (float) ($row['balance'] ?? 0);
        }

        return $this->paginateRows($rows, self::REPORTS_PER_PAGE, $paginate) + [
            'asOf' => $asOf,
            'fromDueDate' => $fromDueDate,
            'toDueDate' => $toDueDate,
            'poNo' => $poNo,
            'dueSort' => $dueSort,
            'totalAmount' => $totalAmount,
            'totalBalance' => $totalBalance,
        ];
    }

    private function buildVoidedReportData(string $fromVoidedDate, string $toVoidedDate, string $poNo, bool $paginate): array
    {
        $db = db_connect();

        $builder = $db->table('purchase_orders po')
            ->select('po.id, po.date, po.po_no, po.due_date, po.total_amount, po.void_reason, po.voided_at')
            ->select('s.name as supplier_name')
            ->select("(po.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('suppliers s', 's.id = po.supplier_id', 'left')
            ->join('payable_allocations pa', 'pa.purchase_order_id = po.id', 'left')
            ->join('payables p', 'p.id = pa.payable_id', 'left')
            ->where('po.voided_at IS NOT NULL', null, false);

        if ($poNo === '' && $fromVoidedDate !== '') {
            $builder->where('po.voided_at >=', $fromVoidedDate . ' 00:00:00');
        }

        if ($poNo === '' && $toVoidedDate !== '') {
            $builder->where('po.voided_at <=', $toVoidedDate . ' 23:59:59');
        }

        if ($poNo !== '') {
            $builder->like('po.po_no', $poNo);
        }

        $rows = $builder
            ->groupBy('po.id')
            ->orderBy('po.voided_at', 'desc')
            ->orderBy('po.id', 'desc')
            ->get()
            ->getResultArray();

        $totalAmount = 0.0;
        $totalBalance = 0.0;
        foreach ($rows as $row) {
            $totalAmount += (float) ($row['total_amount'] ?? 0);
            $totalBalance += (float) ($row['balance'] ?? 0);
        }

        $pagination = $this->paginateRows($rows, self::VOIDED_PER_PAGE, $paginate);
        $pagedRows = $pagination['rows'];
        $itemsByPurchaseOrder = [];
        $allocationsByPurchaseOrder = [];
        $historiesByPurchaseOrder = [];
        $purchaseOrderIds = array_filter(array_map('intval', array_column($pagedRows, 'id') ?? []));

        if (! empty($purchaseOrderIds)) {
            $items = (new PurchaseOrderItemModel())
                ->select('purchase_order_items.*, products.product_name')
                ->join('products', 'products.id = purchase_order_items.product_id', 'left')
                ->whereIn('purchase_order_id', $purchaseOrderIds)
                ->orderBy('purchase_order_id', 'asc')
                ->orderBy('id', 'asc')
                ->findAll();

            foreach ($items as $item) {
                $purchaseOrderId = (int) $item['purchase_order_id'];
                $itemsByPurchaseOrder[$purchaseOrderId][] = $item;
            }

            $allocations = $db->table('payable_allocations pa')
                ->select('pa.purchase_order_id, pa.amount, p.pr_no, p.date')
                ->join('payables p', 'p.id = pa.payable_id', 'left')
                ->where('p.status', 'posted')
                ->whereIn('pa.purchase_order_id', $purchaseOrderIds)
                ->orderBy('p.date', 'asc')
                ->get()
                ->getResultArray();

            foreach ($allocations as $allocation) {
                $purchaseOrderId = (int) $allocation['purchase_order_id'];
                $allocationsByPurchaseOrder[$purchaseOrderId][] = $allocation;
            }

            if ($this->tableExists('purchase_order_histories')) {
                $histories = (new PurchaseOrderHistoryModel())
                    ->select('purchase_order_histories.*, users.name as editor_name, users.username as editor_username')
                    ->join('users', 'users.id = purchase_order_histories.edited_by', 'left')
                    ->whereIn('purchase_order_id', $purchaseOrderIds)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->findAll();

                foreach ($histories as $history) {
                    $historiesByPurchaseOrder[(int) $history['purchase_order_id']][] = $history;
                }
            }
        }

        return $pagination + [
            'fromVoidedDate' => $fromVoidedDate,
            'toVoidedDate' => $toVoidedDate,
            'poNo' => $poNo,
            'totalAmount' => $totalAmount,
            'totalBalance' => $totalBalance,
            'itemsByPurchaseOrder' => $itemsByPurchaseOrder,
            'allocationsByPurchaseOrder' => $allocationsByPurchaseOrder,
            'historiesByPurchaseOrder' => $historiesByPurchaseOrder,
        ];
    }

    private function buildVoidedPosReportData(string $fromVoidedDate, string $toVoidedDate, string $poNo, bool $paginate): array
    {
        $db = db_connect();

        $builder = $db->table('supplier_orders so')
            ->select('so.id, so.date, so.po_no, so.void_reason, so.voided_at')
            ->select('s.name as supplier_name')
            ->select('COALESCE(SUM(soi.qty_ordered), 0) as qty_ordered_total')
            ->select('COALESCE(SUM(soi.qty_picked_up), 0) as qty_picked_up_total')
            ->select('COALESCE(SUM(soi.qty_balance), 0) as qty_balance_total')
            ->join('suppliers s', 's.id = so.supplier_id', 'left')
            ->join('supplier_order_items soi', 'soi.supplier_order_id = so.id', 'left')
            ->where('so.voided_at IS NOT NULL', null, false);

        if ($poNo === '' && $fromVoidedDate !== '') {
            $builder->where('so.voided_at >=', $fromVoidedDate . ' 00:00:00');
        }

        if ($poNo === '' && $toVoidedDate !== '') {
            $builder->where('so.voided_at <=', $toVoidedDate . ' 23:59:59');
        }

        if ($poNo !== '') {
            $builder->like('so.po_no', $poNo);
        }

        $rows = $builder
            ->groupBy('so.id')
            ->orderBy('so.voided_at', 'desc')
            ->orderBy('so.id', 'desc')
            ->get()
            ->getResultArray();

        $totalOrdered = 0.0;
        $totalPickedUp = 0.0;
        $totalBalance = 0.0;
        foreach ($rows as $row) {
            $totalOrdered += (float) ($row['qty_ordered_total'] ?? 0);
            $totalPickedUp += (float) ($row['qty_picked_up_total'] ?? 0);
            $totalBalance += (float) ($row['qty_balance_total'] ?? 0);
        }

        $pagination = $this->paginateRows($rows, self::VOIDED_PER_PAGE, $paginate);
        $pagedRows = $pagination['rows'];
        $supplierOrderIds = array_filter(array_map('intval', array_column($pagedRows, 'id') ?? []));
        $itemsBySupplierOrder = [];
        $historiesBySupplierOrder = [];

        if (! empty($supplierOrderIds)) {
            $items = (new SupplierOrderItemModel())
                ->select('supplier_order_items.*, products.product_name')
                ->join('products', 'products.id = supplier_order_items.product_id', 'left')
                ->whereIn('supplier_order_id', $supplierOrderIds)
                ->orderBy('supplier_order_id', 'asc')
                ->orderBy('id', 'asc')
                ->findAll();

            foreach ($items as $item) {
                $supplierOrderId = (int) $item['supplier_order_id'];
                $itemsBySupplierOrder[$supplierOrderId][] = $item;
            }

            if ($this->tableExists('supplier_order_histories')) {
                $histories = (new SupplierOrderHistoryModel())
                    ->select('supplier_order_histories.*, users.name as editor_name, users.username as editor_username')
                    ->join('users', 'users.id = supplier_order_histories.edited_by', 'left')
                    ->whereIn('supplier_order_id', $supplierOrderIds)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->findAll();

                foreach ($histories as $history) {
                    $historiesBySupplierOrder[(int) $history['supplier_order_id']][] = $history;
                }
            }
        }

        return $pagination + [
            'fromVoidedDate' => $fromVoidedDate,
            'toVoidedDate' => $toVoidedDate,
            'poNo' => $poNo,
            'totalOrdered' => $totalOrdered,
            'totalPickedUp' => $totalPickedUp,
            'totalBalance' => $totalBalance,
            'itemsBySupplierOrder' => $itemsBySupplierOrder,
            'historiesBySupplierOrder' => $historiesBySupplierOrder,
        ];
    }

    private function paginateRows(array $rows, int $perPage, bool $paginate): array
    {
        $pagedRows = $rows;
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $totalRows = count($rows);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        if ($paginate) {
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $perPage;
            $pagedRows = array_slice($rows, $offset, $perPage);
        } else {
            $currentPage = 1;
            $totalPages = 1;
        }

        return [
            'rows' => $pagedRows,
            'allRowsCount' => $totalRows,
            'currentPage' => $currentPage,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
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

    private function resolvePoNoFilter(): string
    {
        return trim((string) ($this->request->getGet('po_no') ?? ''));
    }

    private function resolveVoidedDateRange(): array
    {
        $fromVoidedDate = trim((string) ($this->request->getGet('from_voided_date') ?? ''));
        $toVoidedDate = trim((string) ($this->request->getGet('to_voided_date') ?? ''));

        if ($fromVoidedDate !== '' && $toVoidedDate !== '' && $fromVoidedDate > $toVoidedDate) {
            [$fromVoidedDate, $toVoidedDate] = [$toVoidedDate, $fromVoidedDate];
        }

        return [$fromVoidedDate, $toVoidedDate];
    }

    private function resolveDueSort(): string
    {
        return strtolower((string) $this->request->getGet('due_sort')) === 'desc' ? 'desc' : 'asc';
    }

    private function tableExists(string $table): bool
    {
        $row = db_connect()
            ->query(
                'SELECT COUNT(*) AS found FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            )
            ->getRowArray();

        return (int) ($row['found'] ?? 0) > 0;
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
