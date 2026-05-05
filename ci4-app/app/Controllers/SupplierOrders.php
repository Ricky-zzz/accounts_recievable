<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\SupplierModel;
use App\Models\SupplierOrderHistoryModel;
use App\Models\SupplierOrderItemModel;
use App\Models\SupplierOrderModel;
use App\Services\SupplierOrderHistoryService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class SupplierOrders extends BaseController
{
    private const PER_PAGE = 20;

    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $statusFilter = $this->resolveStatusFilter();
        $result = $this->fetchSupplierOrders(null, $fromDate, $toDate, $poNo, $statusFilter, true);

        return view('supplier_orders/index', [
            'supplier' => null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'statusFilter' => $statusFilter,
            'orders' => $result['orders'],
            'itemsByOrder' => $result['itemsByOrder'],
            'historiesByOrder' => $result['historiesByOrder'],
            'totalOrdered' => $result['totalOrdered'],
            'totalPickedUp' => $result['totalPickedUp'],
            'totalBalance' => $result['totalBalance'],
            'pagerLinks' => $result['pagerLinks'],
            'rowOffset' => $result['rowOffset'],
            'formData' => $this->buildFormData(null),
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
        $statusFilter = $this->resolveStatusFilter();
        $result = $this->fetchSupplierOrders($supplierId, $fromDate, $toDate, $poNo, $statusFilter, true);

        return view('supplier_orders/list', [
            'supplier' => $supplier,
            'supplierId' => $supplierId,
            'selectedSupplierFilter' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'statusFilter' => $statusFilter,
            'orders' => $result['orders'],
            'itemsByOrder' => $result['itemsByOrder'],
            'historiesByOrder' => $result['historiesByOrder'],
            'totalOrdered' => $result['totalOrdered'],
            'totalPickedUp' => $result['totalPickedUp'],
            'totalBalance' => $result['totalBalance'],
            'pagerLinks' => $result['pagerLinks'],
            'rowOffset' => $result['rowOffset'],
            'formData' => $this->buildFormData($supplierId),
        ]);
    }

    public function print()
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $statusFilter = $this->resolveStatusFilter();
        $result = $this->fetchSupplierOrders(null, $fromDate, $toDate, $poNo, $statusFilter);

        return $this->renderPrint(null, $fromDate, $toDate, $poNo, $statusFilter, $result);
    }

    public function supplierPrint(int $supplierId)
    {
        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $statusFilter = $this->resolveStatusFilter();
        $result = $this->fetchSupplierOrders($supplierId, $fromDate, $toDate, $poNo, $statusFilter);

        return $this->renderPrint($supplier, $fromDate, $toDate, $poNo, $statusFilter, $result);
    }

    public function create()
    {
        $supplierId = (int) $this->request->getPost('supplier_id');
        $rules = [
            'supplier_id' => 'required|is_natural_no_zero',
            'po_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the supplier PO form and try again.');
        }

        if (! (new SupplierModel())->find($supplierId)) {
            return redirect()->back()->withInput()->with('error', 'Supplier not found.');
        }

        $poNo = trim((string) $this->request->getPost('po_no'));
        $date = (string) $this->request->getPost('date');
        $items = $this->cleanItems((array) $this->request->getPost('items'));

        if ((new SupplierOrderModel())->where('supplier_id', $supplierId)->where('po_no', $poNo)->first()) {
            return redirect()->back()->withInput()->with('error', 'PO number already exists for this supplier.');
        }

        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one product with quantity.');
        }

        $db = db_connect();
        $db->transStart();

        $orderId = (new SupplierOrderModel())->insert([
            'supplier_id' => $supplierId,
            'po_no' => $poNo,
            'date' => $date,
            'status' => 'active',
        ], true);

        foreach ($items as $index => $item) {
            $items[$index]['supplier_order_id'] = $orderId;
        }

        (new SupplierOrderItemModel())->insertBatch($items);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to save supplier PO.');
        }

        return redirect()
            ->to('suppliers/' . $supplierId . '/supplier-orders?' . http_build_query(['po_no' => $poNo]))
            ->with('success', 'Supplier PO saved.');
    }

    public function update(int $orderId)
    {
        $order = (new SupplierOrderModel())->find($orderId);
        if (! $order) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($order['status'] ?? '') === 'voided') {
            return redirect()->back()->withInput()->with('error', 'Voided supplier POs cannot be edited.');
        }

        if ($this->hasPickedQuantity($orderId)) {
            return redirect()->back()->withInput()->with('error', 'Supplier POs with pickups cannot be edited.');
        }

        $rules = [
            'po_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the supplier PO form and try again.');
        }

        $supplierId = (int) $order['supplier_id'];
        $poNo = trim((string) $this->request->getPost('po_no'));
        $date = (string) $this->request->getPost('date');
        $items = $this->cleanItems((array) $this->request->getPost('items'));

        $existing = (new SupplierOrderModel())
            ->where('supplier_id', $supplierId)
            ->where('po_no', $poNo)
            ->where('id !=', $orderId)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'PO number already exists for this supplier.');
        }

        if (empty($items)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one product with quantity.');
        }

        $oldOrder = $this->fetchSupplierOrderForHistory($orderId) ?? $this->normalizeSupplierOrderForHistory($order);
        $oldItems = $this->fetchSupplierOrderItemsForHistory($orderId);

        $db = db_connect();
        $db->transStart();

        (new SupplierOrderModel())->update($orderId, [
            'po_no' => $poNo,
            'date' => $date,
        ]);

        $itemModel = new SupplierOrderItemModel();
        $itemModel->where('supplier_order_id', $orderId)->delete();
        foreach ($items as $index => $item) {
            $items[$index]['supplier_order_id'] = $orderId;
        }
        $itemModel->insertBatch($items);

        $freshOrder = $this->fetchSupplierOrderForHistory($orderId) ?? array_merge($order, [
            'po_no' => $poNo,
            'date' => $date,
        ]);
        $freshItems = $this->fetchSupplierOrderItemsForHistory($orderId);
        (new SupplierOrderHistoryService())->record(
            $orderId,
            (int) (session('user_id') ?? 0) ?: null,
            'edit',
            $oldOrder,
            $oldItems,
            $freshOrder,
            $freshItems,
            $this->buildChangeSummary($oldOrder, $freshOrder, $oldItems, $freshItems)
        );

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to update supplier PO.');
        }

        return redirect()->back()->with('success', 'Supplier PO updated.');
    }

    public function void(int $orderId)
    {
        $order = (new SupplierOrderModel())->find($orderId);
        if (! $order) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($order['status'] ?? '') === 'voided') {
            return redirect()->back()->with('error', 'Supplier PO is already voided.');
        }

        if ($this->hasPickedQuantity($orderId)) {
            return redirect()->back()->with('error', 'Supplier POs with pickups cannot be voided.');
        }

        $reason = trim((string) $this->request->getPost('void_reason'));
        if ($reason === '') {
            return redirect()->back()->withInput()->with('error', 'Reason for voiding is required.');
        }

        $voidedAt = date('Y-m-d H:i:s');
        $oldOrder = $this->fetchSupplierOrderForHistory($orderId) ?? $this->normalizeSupplierOrderForHistory($order);
        $oldItems = $this->fetchSupplierOrderItemsForHistory($orderId);

        $db = db_connect();
        $db->transStart();

        (new SupplierOrderModel())->update($orderId, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => $voidedAt,
        ]);

        (new SupplierOrderItemModel())
            ->where('supplier_order_id', $orderId)
            ->set(['qty_balance' => 0])
            ->update();

        $freshOrder = $this->fetchSupplierOrderForHistory($orderId) ?? array_merge($oldOrder, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => $voidedAt,
        ]);
        $freshItems = $this->fetchSupplierOrderItemsForHistory($orderId);
        (new SupplierOrderHistoryService())->record(
            $orderId,
            (int) (session('user_id') ?? 0) ?: null,
            'void',
            $oldOrder,
            $oldItems,
            $freshOrder,
            $freshItems,
            'Voided Supplier PO. Reason: ' . $reason
        );

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Failed to void supplier PO.');
        }

        return redirect()->back()->with('success', 'Supplier PO voided.');
    }

    private function cleanItems(array $items): array
    {
        $cleanItems = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty_ordered'] ?? $item['qty'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $cleanItems[] = [
                'product_id' => $productId,
                'qty_ordered' => $qty,
                'qty_picked_up' => 0,
                'qty_balance' => $qty,
            ];
        }

        return $cleanItems;
    }

    private function renderPrint(?array $supplier, string $fromDate, string $toDate, string $poNo, string $statusFilter, array $result)
    {
        $html = view('supplier_orders/print', [
            'supplier' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'statusFilter' => $statusFilter,
            'orders' => $result['orders'],
            'totalOrdered' => $result['totalOrdered'],
            'totalPickedUp' => $result['totalPickedUp'],
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
            ->setHeader('Content-Disposition', 'inline; filename="supplier-pos-report.pdf"')
            ->setBody($dompdf->output());
    }

    private function fetchSupplierOrders(?int $supplierId, string $fromDate, string $toDate, string $poNo, string $statusFilter, bool $paginate = false): array
    {
        $totalsSubquery = $this->supplierOrderTotalsSubquery();
        $model = new SupplierOrderModel();
        $model
            ->select('supplier_orders.*')
            ->select('suppliers.name as supplier_name')
            ->select('COALESCE(totals.qty_ordered_total, 0) as qty_ordered_total')
            ->select('COALESCE(totals.qty_picked_up_total, 0) as qty_picked_up_total')
            ->select('COALESCE(totals.qty_balance_total, 0) as qty_balance_total')
            ->join('suppliers', 'suppliers.id = supplier_orders.supplier_id', 'left')
            ->join('(' . $totalsSubquery . ') totals', 'totals.supplier_order_id = supplier_orders.id', 'left')
            ->where('supplier_orders.voided_at', null);

        $this->applySupplierOrderFilters($model, $supplierId, $fromDate, $toDate, $poNo, 'supplier_orders');
        $this->applyStatusFilter($model, $statusFilter);

        $pagerLinks = '';
        $rowOffset = 0;

        $model
            ->orderBy('supplier_orders.date', 'desc')
            ->orderBy('supplier_orders.id', 'desc');

        if ($paginate) {
            $page = max(1, (int) ($this->request->getGet('page_supplier_orders') ?? $this->request->getGet('page') ?? 1));
            $orders = $model->paginate(self::PER_PAGE, 'supplier_orders');
            $pagerLinks = $model->pager->links('supplier_orders');
            $rowOffset = ($page - 1) * self::PER_PAGE;
        } else {
            $orders = $model->findAll();
        }

        $orderIds = array_filter(array_map('intval', array_column($orders, 'id')));
        $itemsByOrder = [];
        $historiesByOrder = [];

        if (! empty($orderIds)) {
            $items = (new SupplierOrderItemModel())
                ->select('supplier_order_items.*, products.product_name')
                ->join('products', 'products.id = supplier_order_items.product_id', 'left')
                ->whereIn('supplier_order_id', $orderIds)
                ->orderBy('supplier_order_id', 'asc')
                ->orderBy('id', 'asc')
                ->findAll();

            foreach ($items as $item) {
                $orderId = (int) $item['supplier_order_id'];
                $itemsByOrder[$orderId][] = $item;
            }

            if ($this->tableExists('supplier_order_histories')) {
                $histories = (new SupplierOrderHistoryModel())
                    ->select('supplier_order_histories.*, users.name as editor_name, users.username as editor_username')
                    ->join('users', 'users.id = supplier_order_histories.edited_by', 'left')
                    ->whereIn('supplier_order_id', $orderIds)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->findAll();

                foreach ($histories as $history) {
                    $historiesByOrder[(int) $history['supplier_order_id']][] = $history;
                }
            }
        }

        $totals = $this->supplierOrderTotals($supplierId, $fromDate, $toDate, $poNo, $statusFilter);

        return [
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'historiesByOrder' => $historiesByOrder,
            'totalOrdered' => $totals['totalOrdered'],
            'totalPickedUp' => $totals['totalPickedUp'],
            'totalBalance' => $totals['totalBalance'],
            'pagerLinks' => $pagerLinks,
            'rowOffset' => $rowOffset,
        ];
    }

    private function applySupplierOrderFilters($builder, ?int $supplierId, string $fromDate, string $toDate, string $poNo, string $alias): void
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        if ($supplierId !== null) {
            $builder->where($prefix . 'supplier_id', $supplierId);
        }

        if ($fromDate !== '') {
            $builder->where($prefix . 'date >=', $fromDate);
        }

        if ($toDate !== '') {
            $builder->where($prefix . 'date <=', $toDate);
        }

        if ($poNo !== '') {
            $builder->like($prefix . 'po_no', $poNo);
        }
    }

    private function supplierOrderTotals(?int $supplierId, string $fromDate, string $toDate, string $poNo, string $statusFilter): array
    {
        $totalsSubquery = $this->supplierOrderTotalsSubquery();
        $builder = db_connect()->table('supplier_orders so')
            ->select('COALESCE(SUM(totals.qty_ordered_total), 0) as total_ordered')
            ->select('COALESCE(SUM(totals.qty_picked_up_total), 0) as total_picked_up')
            ->select('COALESCE(SUM(totals.qty_balance_total), 0) as total_balance')
            ->join('(' . $totalsSubquery . ') totals', 'totals.supplier_order_id = so.id', 'left')
            ->where('so.voided_at', null);

        $this->applySupplierOrderFilters($builder, $supplierId, $fromDate, $toDate, $poNo, 'so');
        $this->applyStatusFilter($builder, $statusFilter);

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'totalOrdered' => (float) ($row['total_ordered'] ?? 0),
            'totalPickedUp' => (float) ($row['total_picked_up'] ?? 0),
            'totalBalance' => (float) ($row['total_balance'] ?? 0),
        ];
    }

    private function supplierOrderTotalsSubquery(): string
    {
        return db_connect()->table('supplier_order_items')
            ->select('supplier_order_id')
            ->select('SUM(qty_ordered) as qty_ordered_total')
            ->select('SUM(qty_picked_up) as qty_picked_up_total')
            ->select('SUM(qty_balance) as qty_balance_total')
            ->groupBy('supplier_order_id')
            ->getCompiledSelect();
    }

    private function applyStatusFilter($builder, string $statusFilter): void
    {
        if ($statusFilter === 'active') {
            $builder->where('COALESCE(totals.qty_balance_total, 0) > 0.005', null, false);
            return;
        }

        if ($statusFilter === 'closed') {
            $builder->where('COALESCE(totals.qty_balance_total, 0) <= 0.005', null, false);
        }
    }

    private function hasPickedQuantity(int $orderId): bool
    {
        $row = (new SupplierOrderItemModel())
            ->select('SUM(qty_picked_up) as picked')
            ->where('supplier_order_id', $orderId)
            ->first();

        return (float) ($row['picked'] ?? 0) > 0;
    }

    private function fetchSupplierOrderForHistory(int $orderId): ?array
    {
        $order = (new SupplierOrderModel())
            ->select('supplier_orders.*, suppliers.name as supplier_name')
            ->join('suppliers', 'suppliers.id = supplier_orders.supplier_id', 'left')
            ->where('supplier_orders.id', $orderId)
            ->first();

        return $order ? $this->normalizeSupplierOrderForHistory($order) : null;
    }

    private function normalizeSupplierOrderForHistory(array $order): array
    {
        return [
            'id' => (int) ($order['id'] ?? 0),
            'supplier_id' => (int) ($order['supplier_id'] ?? 0),
            'supplier_name' => $order['supplier_name'] ?? '',
            'po_no' => $order['po_no'] ?? '',
            'date' => $order['date'] ?? '',
            'status' => $order['status'] ?? '',
            'void_reason' => $order['void_reason'] ?? null,
            'voided_at' => $order['voided_at'] ?? null,
        ];
    }

    private function fetchSupplierOrderItemsForHistory(int $orderId): array
    {
        return (new SupplierOrderItemModel())
            ->select('supplier_order_items.*, products.product_name')
            ->join('products', 'products.id = supplier_order_items.product_id', 'left')
            ->where('supplier_order_id', $orderId)
            ->orderBy('id', 'asc')
            ->findAll();
    }

    private function buildChangeSummary(array $oldOrder, array $newOrder, array $oldItems, array $newItems): string
    {
        $changes = [];

        if (($oldOrder['po_no'] ?? '') !== ($newOrder['po_no'] ?? '')) {
            $changes[] = 'PO# changed from ' . ($oldOrder['po_no'] ?? '-') . ' to ' . ($newOrder['po_no'] ?? '-');
        }

        if (($oldOrder['date'] ?? '') !== ($newOrder['date'] ?? '')) {
            $changes[] = 'Date changed from ' . ($oldOrder['date'] ?? '-') . ' to ' . ($newOrder['date'] ?? '-');
        }

        $oldTotal = $this->sumHistoryItems($oldItems);
        $newTotal = $this->sumHistoryItems($newItems);
        if (abs($oldTotal - $newTotal) > 0.005) {
            $changes[] = 'Ordered quantity changed from ' . number_format($oldTotal, 2) . ' to ' . number_format($newTotal, 2);
        }

        if (count($oldItems) !== count($newItems)) {
            $changes[] = 'Item count changed from ' . count($oldItems) . ' to ' . count($newItems);
        }

        return implode('; ', $changes) ?: 'Supplier PO details updated.';
    }

    private function sumHistoryItems(array $items): float
    {
        return array_reduce(
            $items,
            static fn (float $sum, array $item): float => $sum + (float) ($item['qty_ordered'] ?? 0),
            0.0
        );
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

    private function buildFormData(?int $supplierId): array
    {
        $supplierModel = new SupplierModel();

        return [
            'selectedSupplier' => $supplierId ? $supplierModel->find($supplierId) : null,
            'suppliers' => $supplierModel->orderBy('name', 'asc')->findAll(),
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

    private function resolveStatusFilter(): string
    {
        $status = strtolower(trim((string) ($this->request->getGet('status') ?? 'all')));

        return in_array($status, ['all', 'active', 'closed'], true) ? $status : 'all';
    }

}
