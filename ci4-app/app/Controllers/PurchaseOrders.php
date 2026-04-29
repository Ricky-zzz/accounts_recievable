<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\PayableLedgerModel;
use App\Models\ProductModel;
use App\Models\PurchaseOrderHistoryModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\PurchaseOrderModel;
use App\Models\SupplierModel;
use App\Models\UserModel;
use App\Services\PayablePostingService;
use App\Services\PurchaseOrderHistoryService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class PurchaseOrders extends BaseController
{
    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $result = $this->fetchPurchaseOrders(null, $fromDate, $toDate, $poNo, true);

        return view('purchase_orders/index', [
            'supplier' => null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'purchaseOrders' => $result['purchaseOrders'],
            'itemsByPurchaseOrder' => $result['itemsByPurchaseOrder'],
            'allocationsByPurchaseOrder' => $result['allocationsByPurchaseOrder'],
            'historiesByPurchaseOrder' => $result['historiesByPurchaseOrder'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
            'pagerLinks' => $result['pagerLinks'],
            'rowOffset' => $result['rowOffset'],
            'formData' => $this->buildFormData(null),
            'quickPayData' => $this->buildQuickPayData(),
            'actionData' => $this->buildActionData(),
        ]);
    }

    public function supplierList(int $supplierId): string
    {
        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $result = $this->fetchPurchaseOrders($supplierId, $fromDate, $toDate, $poNo, true);

        return view('purchase_orders/list', [
            'supplier' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'purchaseOrders' => $result['purchaseOrders'],
            'itemsByPurchaseOrder' => $result['itemsByPurchaseOrder'],
            'allocationsByPurchaseOrder' => $result['allocationsByPurchaseOrder'],
            'historiesByPurchaseOrder' => $result['historiesByPurchaseOrder'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
            'pagerLinks' => $result['pagerLinks'],
            'rowOffset' => $result['rowOffset'],
            'formData' => $this->buildFormData($supplierId),
            'quickPayData' => $this->buildQuickPayData(),
            'actionData' => $this->buildActionData(),
        ]);
    }

    public function print()
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $supplierId = (int) $this->request->getGet('supplier_id') ?: null;
        $supplier = $supplierId ? (new SupplierModel())->find($supplierId) : null;
        $result = $this->fetchPurchaseOrders($supplierId, $fromDate, $toDate, $poNo);

        $html = view('purchase_orders/print', [
            'supplier' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'purchaseOrders' => $result['purchaseOrders'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="purchase-orders-report.pdf"')
            ->setBody($dompdf->output());
    }

    public function create()
    {
        $supplierId = (int) $this->request->getPost('supplier_id');
        $rules = [
            'supplier_id' => 'required|is_natural_no_zero',
            'po_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
            'payment_term' => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the purchase order form and try again.');
        }

        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            return redirect()->back()->withInput()->with('error', 'Supplier not found.');
        }

        $poNo = trim((string) $this->request->getPost('po_no'));
        $date = (string) $this->request->getPost('date');
        $postedPaymentTerm = trim((string) $this->request->getPost('payment_term'));
        $items = $this->request->getPost('items');

        if ((new PurchaseOrderModel())->where('supplier_id', $supplierId)->where('po_no', $poNo)->first()) {
            return redirect()->back()->withInput()->with('error', 'PO number already exists for this supplier.');
        }

        if (! is_array($items) || count($items) === 0) {
            return redirect()->back()->withInput()->with('error', 'At least one item is required.');
        }

        $cleanItems = $this->cleanItems($items);
        if (empty($cleanItems)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one valid item with quantity.');
        }

        $total = array_reduce($cleanItems, static fn (float $sum, array $item): float => $sum + (float) $item['line_total'], 0.0);
        $previousBalance = $this->latestLedgerBalance($supplierId);
        $creditLimit = $supplier['credit_limit'] ?? null;
        if ($creditLimit !== null && $creditLimit !== '' && (float) $creditLimit > 0 && $previousBalance + $total > (float) $creditLimit) {
            return redirect()->back()->withInput()->with(
                'error',
                'Credit limit exceeded. Projected balance ' . number_format($previousBalance + $total, 2)
                . ' is greater than supplier credit limit ' . number_format((float) $creditLimit, 2) . '.'
            );
        }

        $effectivePaymentTerm = $postedPaymentTerm === '' ? (int) ($supplier['payment_term'] ?? 0) : (int) $postedPaymentTerm;
        $effectivePaymentTerm = max(0, $effectivePaymentTerm);
        $dueDate = $this->calculateDueDate($date, $effectivePaymentTerm);

        $db = db_connect();
        $db->transStart();

        $purchaseOrderId = (new PurchaseOrderModel())->insert([
            'supplier_id' => $supplierId,
            'po_no' => $poNo,
            'date' => $date,
            'payment_term' => $effectivePaymentTerm,
            'due_date' => $dueDate,
            'total_amount' => $total,
            'status' => 'active',
        ], true);

        foreach ($cleanItems as $index => $item) {
            $cleanItems[$index]['purchase_order_id'] = $purchaseOrderId;
        }
        (new PurchaseOrderItemModel())->insertBatch($cleanItems);

        $firstItem = $cleanItems[0] ?? null;
        (new PayableLedgerModel())->insert([
            'supplier_id' => $supplierId,
            'entry_date' => $date,
            'po_no' => $poNo,
            'pr_no' => null,
            'qty' => $firstItem['qty'] ?? null,
            'price' => $firstItem['unit_price'] ?? null,
            'payables' => $total,
            'payment' => 0,
            'account_title' => null,
            'other_accounts' => 0,
            'balance' => $previousBalance + $total,
            'purchase_order_id' => $purchaseOrderId,
            'payable_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to save purchase order.');
        }

        $query = http_build_query(['po_no' => $poNo, 'from_date' => $date, 'to_date' => $date]);

        return redirect()->to('suppliers/' . $supplierId . '/purchase-orders?' . $query)->with('success', 'Purchase order saved.');
    }

    public function update(int $purchaseOrderId)
    {
        $purchaseOrder = $this->fetchPurchaseOrderWithBalance($purchaseOrderId);
        if (! $purchaseOrder) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! empty($purchaseOrder['voided_at']) || ($purchaseOrder['status'] ?? '') === 'voided') {
            return redirect()->back()->withInput()->with('error', 'Voided purchase orders cannot be edited.');
        }

        if ((float) ($purchaseOrder['allocated_amount'] ?? 0) > 0) {
            return redirect()->back()->withInput()->with('error', 'Purchase orders with payments cannot be edited.');
        }

        $rules = [
            'po_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
            'payment_term' => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the purchase order form and try again.');
        }

        $supplierId = (int) $purchaseOrder['supplier_id'];
        $poNo = trim((string) $this->request->getPost('po_no'));
        $date = (string) $this->request->getPost('date');
        $postedPaymentTerm = trim((string) $this->request->getPost('payment_term'));
        $items = $this->request->getPost('items');

        $existing = (new PurchaseOrderModel())
            ->where('supplier_id', $supplierId)
            ->where('po_no', $poNo)
            ->where('id !=', $purchaseOrderId)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'PO number already exists for this supplier.');
        }

        $cleanItems = is_array($items) ? $this->cleanItems($items) : [];
        if (empty($cleanItems)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one valid item with quantity.');
        }

        $total = array_reduce($cleanItems, static fn (float $sum, array $item): float => $sum + (float) $item['line_total'], 0.0);
        $effectivePaymentTerm = $postedPaymentTerm === '' ? (int) ($purchaseOrder['payment_term'] ?? 0) : (int) $postedPaymentTerm;
        $effectivePaymentTerm = max(0, $effectivePaymentTerm);
        $dueDate = $this->calculateDueDate($date, $effectivePaymentTerm);
        $oldItems = $this->fetchPurchaseOrderItems($purchaseOrderId);
        $oldTotal = (float) ($purchaseOrder['total_amount'] ?? 0);

        $newPurchaseOrder = [
            'supplier_id' => $supplierId,
            'po_no' => $poNo,
            'date' => $date,
            'payment_term' => $effectivePaymentTerm,
            'due_date' => $dueDate,
            'total_amount' => $total,
            'status' => $purchaseOrder['status'] ?? 'active',
        ];

        $db = db_connect();
        $db->transStart();

        (new PurchaseOrderModel())->update($purchaseOrderId, $newPurchaseOrder);
        $itemModel = new PurchaseOrderItemModel();
        $itemModel->where('purchase_order_id', $purchaseOrderId)->delete();
        foreach ($cleanItems as $index => $item) {
            $cleanItems[$index]['purchase_order_id'] = $purchaseOrderId;
        }
        $itemModel->insertBatch($cleanItems);

        $difference = round($total - $oldTotal, 2);
        if (abs($difference) > 0.005) {
            $this->insertAdjustmentLedgerRow($supplierId, $purchaseOrderId, $poNo, $difference);
        }

        $freshPurchaseOrder = $this->fetchPurchaseOrderWithBalance($purchaseOrderId) ?? array_merge($purchaseOrder, $newPurchaseOrder);
        $freshItems = $this->fetchPurchaseOrderItems($purchaseOrderId);
        (new PurchaseOrderHistoryService())->record(
            $purchaseOrderId,
            (int) (session('user_id') ?? 0) ?: null,
            'edit',
            $purchaseOrder,
            $oldItems,
            $freshPurchaseOrder,
            $freshItems,
            $this->buildChangeSummary($purchaseOrder, $freshPurchaseOrder, $oldItems, $freshItems)
        );

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to update purchase order.');
        }

        return redirect()->back()->with('success', 'Purchase order updated.');
    }

    public function void(int $purchaseOrderId)
    {
        $purchaseOrder = $this->fetchPurchaseOrderWithBalance($purchaseOrderId);
        if (! $purchaseOrder) {
            throw PageNotFoundException::forPageNotFound();
        }

        $reason = trim((string) $this->request->getPost('void_reason'));
        if ($reason === '') {
            return redirect()->back()->withInput()->with('error', 'Reason for voiding is required.');
        }

        if (! empty($purchaseOrder['voided_at']) || ($purchaseOrder['status'] ?? '') === 'voided') {
            return redirect()->back()->with('error', 'Purchase order is already voided.');
        }

        if ((float) ($purchaseOrder['allocated_amount'] ?? 0) > 0) {
            return redirect()->back()->with('error', 'Purchase orders with partial or full payments cannot be voided.');
        }

        $oldItems = $this->fetchPurchaseOrderItems($purchaseOrderId);
        $voidedAt = date('Y-m-d H:i:s');

        $db = db_connect();
        $db->transStart();

        (new PurchaseOrderModel())->update($purchaseOrderId, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => $voidedAt,
        ]);

        $this->insertVoidLedgerRow($purchaseOrder, $voidedAt);
        $newPurchaseOrder = $this->fetchPurchaseOrderWithBalance($purchaseOrderId) ?? array_merge($purchaseOrder, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => $voidedAt,
        ]);

        (new PurchaseOrderHistoryService())->record(
            $purchaseOrderId,
            (int) (session('user_id') ?? 0) ?: null,
            'void',
            $purchaseOrder,
            $oldItems,
            $newPurchaseOrder,
            $oldItems,
            'Voided purchase order. Reason: ' . $reason
        );

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Failed to void purchase order.');
        }

        return redirect()->back()->with('success', 'Purchase order voided.');
    }

    private function cleanItems(array $items): array
    {
        $cleanItems = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $cleanItems[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $qty * $unitPrice,
            ];
        }

        return $cleanItems;
    }

    private function fetchPurchaseOrderWithBalance(int $purchaseOrderId): ?array
    {
        $row = db_connect()->table('purchase_orders po')
            ->select('po.*')
            ->select('s.name as supplier_name')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(po.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('suppliers s', 's.id = po.supplier_id', 'left')
            ->join('payable_allocations pa', 'pa.purchase_order_id = po.id', 'left')
            ->join('payables p', 'p.id = pa.payable_id', 'left')
            ->where('po.id', $purchaseOrderId)
            ->groupBy('po.id')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function fetchPurchaseOrderItems(int $purchaseOrderId): array
    {
        return (new PurchaseOrderItemModel())
            ->select('purchase_order_items.*, products.product_name')
            ->join('products', 'products.id = purchase_order_items.product_id', 'left')
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderBy('purchase_order_items.id', 'asc')
            ->findAll();
    }

    private function insertVoidLedgerRow(array $purchaseOrder, string $voidedAt): void
    {
        $supplierId = (int) $purchaseOrder['supplier_id'];
        $amount = (float) $purchaseOrder['total_amount'];
        $previousBalance = $this->latestLedgerBalance($supplierId);

        (new PayableLedgerModel())->insert([
            'supplier_id' => $supplierId,
            'entry_date' => date('Y-m-d', strtotime($voidedAt)),
            'po_no' => (string) $purchaseOrder['po_no'],
            'pr_no' => null,
            'qty' => null,
            'price' => null,
            'payables' => 0,
            'payment' => 0,
            'account_title' => 'Voided',
            'other_accounts' => $amount,
            'balance' => $previousBalance - $amount,
            'purchase_order_id' => (int) $purchaseOrder['id'],
            'payable_id' => null,
            'created_at' => $voidedAt,
        ]);
    }

    private function insertAdjustmentLedgerRow(int $supplierId, int $purchaseOrderId, string $poNo, float $difference): void
    {
        $previousBalance = $this->latestLedgerBalance($supplierId);
        $payables = $difference > 0 ? $difference : 0;
        $otherAccounts = $difference < 0 ? abs($difference) : 0;

        (new PayableLedgerModel())->insert([
            'supplier_id' => $supplierId,
            'entry_date' => date('Y-m-d'),
            'po_no' => $poNo,
            'pr_no' => null,
            'qty' => null,
            'price' => null,
            'payables' => $payables,
            'payment' => 0,
            'account_title' => 'Purchase Order Adjustment',
            'other_accounts' => $otherAccounts,
            'balance' => $previousBalance + $payables - $otherAccounts,
            'purchase_order_id' => $purchaseOrderId,
            'payable_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function latestLedgerBalance(int $supplierId): float
    {
        $lastLedger = (new PayableLedgerModel())
            ->select('balance')
            ->where('supplier_id', $supplierId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return (float) ($lastLedger['balance'] ?? 0);
    }

    private function buildChangeSummary(array $oldPurchaseOrder, array $newPurchaseOrder, array $oldItems, array $newItems): string
    {
        $changes = [];
        foreach (['po_no' => 'PO#', 'date' => 'Date', 'due_date' => 'Due date', 'payment_term' => 'Term'] as $field => $label) {
            if ((string) ($oldPurchaseOrder[$field] ?? '') !== (string) ($newPurchaseOrder[$field] ?? '')) {
                $changes[] = $label . ' changed from ' . ($oldPurchaseOrder[$field] ?? '-') . ' to ' . ($newPurchaseOrder[$field] ?? '-');
            }
        }

        $oldTotal = (float) ($oldPurchaseOrder['total_amount'] ?? 0);
        $newTotal = (float) ($newPurchaseOrder['total_amount'] ?? 0);
        if (abs($oldTotal - $newTotal) > 0.005) {
            $changes[] = 'Total changed from ' . number_format($oldTotal, 2) . ' to ' . number_format($newTotal, 2);
        }

        if (count($oldItems) !== count($newItems)) {
            $changes[] = 'Item count changed from ' . count($oldItems) . ' to ' . count($newItems);
        }

        return empty($changes) ? 'Purchase order details updated.' : implode('; ', $changes) . '.';
    }

    private function buildFormData(?int $supplierId): array
    {
        $supplierModel = new SupplierModel();
        $selectedSupplier = $supplierId ? $supplierModel->find($supplierId) : null;
        $defaultPaymentTerm = old('payment_term');
        if ($selectedSupplier && ($defaultPaymentTerm === null || $defaultPaymentTerm === '')) {
            $defaultPaymentTerm = $selectedSupplier['payment_term'] ?? '';
        }

        return [
            'selectedSupplier' => $selectedSupplier,
            'suppliers' => $supplierModel->orderBy('name', 'asc')->findAll(),
            'defaultPaymentTerm' => $defaultPaymentTerm,
            'products' => (new ProductModel())->orderBy('product_name', 'asc')->findAll(),
        ];
    }

    private function buildQuickPayData(): array
    {
        $userId = (int) (session('user_id') ?? 0);
        $assignedUser = $userId > 0 ? (new UserModel())->find($userId) : null;
        $activeRange = (new PayablePostingService())->getActiveReceiptRange($userId);

        return [
            'assignedUser' => $assignedUser,
            'activeReceipt' => $activeRange ? (int) $activeRange['next_no'] : null,
            'rangeEnd' => $activeRange ? (int) $activeRange['end_no'] : null,
            'banks' => (new BankModel())->orderBy('bank_name', 'asc')->findAll(),
        ];
    }

    private function buildActionData(): array
    {
        return [
            'products' => (new ProductModel())->orderBy('product_name', 'asc')->findAll(),
        ];
    }

    private function resolveDateRange(): array
    {
        return [
            trim((string) ($this->request->getGet('from_date') ?? '')),
            trim((string) ($this->request->getGet('to_date') ?? '')),
        ];
    }

    private function resolvePoNoFilter(): string
    {
        return trim((string) ($this->request->getGet('po_no') ?? ''));
    }

    private function applyPurchaseOrderFilters($builder, ?int $supplierId, string $fromDate, string $toDate, string $poNo)
    {
        $builder->where('po.voided_at', null);

        if ($supplierId !== null) {
            $builder->where('po.supplier_id', $supplierId);
        }
        if ($fromDate !== '') {
            $builder->where('po.date >=', $fromDate);
        }
        if ($toDate !== '') {
            $builder->where('po.date <=', $toDate);
        }
        if ($poNo !== '') {
            $builder->like('po.po_no', $poNo);
        }

        return $builder;
    }

    private function fetchPurchaseOrders(?int $supplierId, string $fromDate, string $toDate, string $poNo = '', bool $paginate = false): array
    {
        $db = db_connect();
        $builder = $db->table('purchase_orders po');
        $builder
            ->select('po.id, po.supplier_id, po.po_no, po.date, po.payment_term, po.due_date, po.total_amount, po.status, po.void_reason, po.voided_at')
            ->select('s.name as supplier_name')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(po.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('suppliers s', 's.id = po.supplier_id', 'left')
            ->join('payable_allocations pa', 'pa.purchase_order_id = po.id', 'left')
            ->join('payables p', 'p.id = pa.payable_id', 'left');

        $this->applyPurchaseOrderFilters($builder, $supplierId, $fromDate, $toDate, $poNo);

        $totalBuilder = $db->table('purchase_orders po')
            ->select('COALESCE(SUM(po.total_amount), 0) as total_amount')
            ->select('COALESCE(SUM(po.total_amount - COALESCE(payables_summary.allocated_amount, 0)), 0) as total_balance')
            ->join(
                "(SELECT pa.purchase_order_id, SUM(pa.amount) as allocated_amount FROM payable_allocations pa JOIN payables p ON p.id = pa.payable_id WHERE p.status = 'posted' GROUP BY pa.purchase_order_id) payables_summary",
                'payables_summary.purchase_order_id = po.id',
                'left'
            );
        $this->applyPurchaseOrderFilters($totalBuilder, $supplierId, $fromDate, $toDate, $poNo);
        $totals = $totalBuilder->get()->getRowArray() ?? [];

        $pagerLinks = '';
        $rowOffset = 0;
        if ($paginate) {
            $perPage = 20;
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $countBuilder = $db->table('purchase_orders po')->select('COUNT(*) as total');
            $this->applyPurchaseOrderFilters($countBuilder, $supplierId, $fromDate, $toDate, $poNo);
            $totalRows = (int) (($countBuilder->get()->getRowArray()['total'] ?? 0));
            $rowOffset = ($page - 1) * $perPage;
            $pagerLinks = service('pager')->makeLinks($page, $perPage, $totalRows, 'default_full');
            $builder->limit($perPage, $rowOffset);
        }

        $purchaseOrders = $builder
            ->groupBy('po.id')
            ->orderBy('po.date', 'desc')
            ->orderBy('po.id', 'desc')
            ->get()
            ->getResultArray();

        $purchaseOrderIds = array_filter(array_map('intval', array_column($purchaseOrders, 'id')));
        $itemsByPurchaseOrder = [];
        $allocationsByPurchaseOrder = [];
        $historiesByPurchaseOrder = [];

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

            $allocations = $db->table('payable_allocations pa')
                ->select('pa.purchase_order_id, pa.amount, p.pr_no, p.date')
                ->join('payables p', 'p.id = pa.payable_id', 'left')
                ->where('p.status', 'posted')
                ->whereIn('pa.purchase_order_id', $purchaseOrderIds)
                ->orderBy('p.date', 'asc')
                ->get()
                ->getResultArray();

            foreach ($allocations as $allocation) {
                $allocationsByPurchaseOrder[(int) $allocation['purchase_order_id']][] = $allocation;
            }

            if ($db->tableExists('purchase_order_histories')) {
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

        return [
            'purchaseOrders' => $purchaseOrders,
            'itemsByPurchaseOrder' => $itemsByPurchaseOrder,
            'allocationsByPurchaseOrder' => $allocationsByPurchaseOrder,
            'historiesByPurchaseOrder' => $historiesByPurchaseOrder,
            'totalAmount' => (float) ($totals['total_amount'] ?? 0),
            'totalBalance' => (float) ($totals['total_balance'] ?? 0),
            'pagerLinks' => $pagerLinks,
            'rowOffset' => $rowOffset,
        ];
    }

    private function calculateDueDate(string $date, int $paymentTerm): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        return date('Y-m-d', strtotime('+' . $paymentTerm . ' days', $timestamp));
    }
}
