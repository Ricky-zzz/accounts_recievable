<?php

namespace App\Controllers;

use App\Models\PayableLedgerModel;
use App\Models\ProductModel;
use App\Models\SupplierModel;
use App\Models\SupplierOrderItemModel;
use App\Models\SupplierOrderModel;
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
        $result = $this->fetchSupplierOrders(null, $fromDate, $toDate, $poNo, true);

        return view('supplier_orders/index', [
            'supplier' => null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'orders' => $result['orders'],
            'itemsByOrder' => $result['itemsByOrder'],
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
        $result = $this->fetchSupplierOrders($supplierId, $fromDate, $toDate, $poNo, true);

        return view('supplier_orders/list', [
            'supplier' => $supplier,
            'supplierId' => $supplierId,
            'selectedSupplierFilter' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
            'orders' => $result['orders'],
            'itemsByOrder' => $result['itemsByOrder'],
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
        $result = $this->fetchSupplierOrders(null, $fromDate, $toDate, $poNo);

        return $this->renderPrint(null, $fromDate, $toDate, $poNo, $result);
    }

    public function supplierPrint(int $supplierId)
    {
        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $poNo = $this->resolvePoNoFilter();
        $result = $this->fetchSupplierOrders($supplierId, $fromDate, $toDate, $poNo);

        return $this->renderPrint($supplier, $fromDate, $toDate, $poNo, $result);
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
        $this->upsertSupplierOrderLedgerRow($orderId);

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
        $this->upsertSupplierOrderLedgerRow($orderId);

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

        $this->insertSupplierOrderVoidLedgerRow($orderId, $voidedAt);

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

    private function renderPrint(?array $supplier, string $fromDate, string $toDate, string $poNo, array $result)
    {
        $html = view('supplier_orders/print', [
            'supplier' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'poNo' => $poNo,
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

    private function fetchSupplierOrders(?int $supplierId, string $fromDate, string $toDate, string $poNo, bool $paginate = false): array
    {
        $model = new SupplierOrderModel();
        $model
            ->select('supplier_orders.*')
            ->select('suppliers.name as supplier_name')
            ->join('suppliers', 'suppliers.id = supplier_orders.supplier_id', 'left')
            ->where('supplier_orders.voided_at', null);

        $this->applySupplierOrderFilters($model, $supplierId, $fromDate, $toDate, $poNo, 'supplier_orders');

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
        }

        foreach ($orders as $index => $order) {
            $ordered = 0.0;
            $picked = 0.0;
            $balance = 0.0;

            foreach ($itemsByOrder[(int) $order['id']] ?? [] as $item) {
                $ordered += (float) ($item['qty_ordered'] ?? 0);
                $picked += (float) ($item['qty_picked_up'] ?? 0);
                $balance += (float) ($item['qty_balance'] ?? 0);
            }

            $orders[$index]['qty_ordered_total'] = $ordered;
            $orders[$index]['qty_picked_up_total'] = $picked;
            $orders[$index]['qty_balance_total'] = $balance;
        }

        $totals = $this->supplierOrderTotals($supplierId, $fromDate, $toDate, $poNo);

        return [
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
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

    private function supplierOrderTotals(?int $supplierId, string $fromDate, string $toDate, string $poNo): array
    {
        $builder = db_connect()->table('supplier_orders so')
            ->select('COALESCE(SUM(soi.qty_ordered), 0) as total_ordered')
            ->select('COALESCE(SUM(soi.qty_picked_up), 0) as total_picked_up')
            ->select('COALESCE(SUM(soi.qty_balance), 0) as total_balance')
            ->join('supplier_order_items soi', 'soi.supplier_order_id = so.id', 'left')
            ->where('so.voided_at', null);

        $this->applySupplierOrderFilters($builder, $supplierId, $fromDate, $toDate, $poNo, 'so');

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'totalOrdered' => (float) ($row['total_ordered'] ?? 0),
            'totalPickedUp' => (float) ($row['total_picked_up'] ?? 0),
            'totalBalance' => (float) ($row['total_balance'] ?? 0),
        ];
    }

    private function hasPickedQuantity(int $orderId): bool
    {
        $row = (new SupplierOrderItemModel())
            ->select('SUM(qty_picked_up) as picked')
            ->where('supplier_order_id', $orderId)
            ->first();

        return (float) ($row['picked'] ?? 0) > 0;
    }

    private function upsertSupplierOrderLedgerRow(int $orderId): void
    {
        $order = (new SupplierOrderModel())->find($orderId);
        if (! $order) {
            return;
        }

        $summary = $this->supplierOrderItemSummary($orderId);
        $ledgerModel = new PayableLedgerModel();
        $existing = $ledgerModel
            ->where('supplier_order_id', $orderId)
            ->where('purchase_order_id', null)
            ->where('payable_id', null)
            ->where('account_title', 'Supplier PO')
            ->first();

        $data = [
            'supplier_id' => (int) $order['supplier_id'],
            'entry_date' => (string) $order['date'],
            'po_no' => null,
            'pr_no' => null,
            'qty' => $summary['qty_ordered'],
            'price' => null,
            'payables' => 0,
            'payment' => 0,
            'account_title' => 'Supplier PO',
            'other_accounts' => 0,
            'balance' => $this->ledgerBalanceAsOf((int) $order['supplier_id'], (string) $order['date'], $orderId),
            'supplier_order_id' => $orderId,
            'supplier_order_item_id' => $summary['first_item_id'],
            'po_balance' => $summary['qty_balance'],
            'purchase_order_id' => null,
            'payable_id' => null,
        ];

        if ($existing) {
            $ledgerModel->update((int) $existing['id'], $data);
            return;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $ledgerModel->insert($data);
    }

    private function insertSupplierOrderVoidLedgerRow(int $orderId, string $voidedAt): void
    {
        $order = (new SupplierOrderModel())->find($orderId);
        if (! $order) {
            return;
        }

        $summary = $this->supplierOrderItemSummary($orderId);

        (new PayableLedgerModel())->insert([
            'supplier_id' => (int) $order['supplier_id'],
            'entry_date' => date('Y-m-d', strtotime($voidedAt)),
            'po_no' => null,
            'pr_no' => null,
            'qty' => null,
            'price' => null,
            'payables' => 0,
            'payment' => 0,
            'account_title' => 'Voided Supplier PO',
            'other_accounts' => 0,
            'balance' => $this->ledgerBalanceAsOf((int) $order['supplier_id'], date('Y-m-d', strtotime($voidedAt))),
            'supplier_order_id' => $orderId,
            'supplier_order_item_id' => $summary['first_item_id'],
            'po_balance' => 0,
            'purchase_order_id' => null,
            'payable_id' => null,
            'created_at' => $voidedAt,
        ]);
    }

    private function supplierOrderItemSummary(int $orderId): array
    {
        $items = (new SupplierOrderItemModel())
            ->where('supplier_order_id', $orderId)
            ->orderBy('id', 'asc')
            ->findAll();

        $ordered = 0.0;
        $balance = 0.0;
        $firstItemId = null;

        foreach ($items as $item) {
            $firstItemId ??= (int) ($item['id'] ?? 0);
            $ordered += (float) ($item['qty_ordered'] ?? 0);
            $balance += (float) ($item['qty_balance'] ?? 0);
        }

        return [
            'first_item_id' => $firstItemId,
            'qty_ordered' => $ordered,
            'qty_balance' => $balance,
        ];
    }

    private function ledgerBalanceAsOf(int $supplierId, string $date, ?int $excludeSupplierOrderId = null): float
    {
        $ledgerModel = new PayableLedgerModel();
        $ledgerModel
            ->select('balance')
            ->where('supplier_id', $supplierId)
            ->where('entry_date <=', $date);

        if ($excludeSupplierOrderId !== null) {
            $ledgerModel->groupStart()
                ->where('supplier_order_id !=', $excludeSupplierOrderId)
                ->orWhere('supplier_order_id', null)
                ->groupEnd();
        }

        $row = $ledgerModel
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return (float) ($row['balance'] ?? 0);
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

}
