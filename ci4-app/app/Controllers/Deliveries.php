<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\ClientModel;
use App\Models\DeliveryHistoryModel;
use App\Models\DeliveryItemModel;
use App\Models\DeliveryModel;
use App\Models\DeliveryPickupAllocationModel;
use App\Models\LedgerModel;
use App\Models\ProductClientPriceModel;
use App\Models\ProductModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\PurchaseOrderModel;
use App\Models\UserModel;
use App\Services\DeliveryHistoryService;
use App\Services\PaymentPostingService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class Deliveries extends BaseController
{
    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $drNo = $this->resolveDrNoFilter();
        $result = $this->fetchDeliveries(null, $fromDate, $toDate, $drNo, true);

        return view('deliveries/index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'drNo' => $drNo,
            'deliveries' => $result['deliveries'],
            'itemsByDelivery' => $result['itemsByDelivery'],
            'allocationsByDelivery' => $result['allocationsByDelivery'],
            'pickupAllocationsByDelivery' => $result['pickupAllocationsByDelivery'],
            'historiesByDelivery' => $result['historiesByDelivery'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
            'pagerLinks' => $result['pagerLinks'],
            'rowOffset' => $result['rowOffset'],
            'quickPayData' => $this->buildQuickPayData(),
            'deliveryActionData' => $this->buildDeliveryActionData(),
        ]);
    }

    public function print()
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $drNo = $this->resolveDrNoFilter();
        $result = $this->fetchDeliveries(null, $fromDate, $toDate, $drNo);

        $html = view('deliveries/print', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'drNo' => $drNo,
            'deliveries' => $result['deliveries'],
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
            ->setHeader('Content-Disposition', 'inline; filename="deliveries-report.pdf"')
            ->setBody($dompdf->output());
    }

    public function clientList(int $clientId): string
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);
        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $drNo = $this->resolveDrNoFilter();
        $result = $this->fetchDeliveries($clientId, $fromDate, $toDate, $drNo, true);
        $formData = $this->buildFormData($clientId, null, [], true);

        return view('deliveries/list', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'drNo' => $drNo,
            'deliveries' => $result['deliveries'],
            'itemsByDelivery' => $result['itemsByDelivery'],
            'allocationsByDelivery' => $result['allocationsByDelivery'],
            'pickupAllocationsByDelivery' => $result['pickupAllocationsByDelivery'],
            'historiesByDelivery' => $result['historiesByDelivery'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
            'pagerLinks' => $result['pagerLinks'],
            'rowOffset' => $result['rowOffset'],
            'deliveryFormData' => $formData,
            'quickPayData' => $this->buildQuickPayData(),
            'deliveryActionData' => $this->buildDeliveryActionData(),
        ]);
    }

    public function listPrint(int $clientId)
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);
        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $drNo = $this->resolveDrNoFilter();
        $result = $this->fetchDeliveries($clientId, $fromDate, $toDate, $drNo);

        $html = view('deliveries/listprint', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'drNo' => $drNo,
            'deliveries' => $result['deliveries'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="deliveries-list-report.pdf"')
            ->setBody($dompdf->output());
    }

    public function createForm($clientId = null)
    {
        return view('deliveries/form', $this->buildFormData($clientId));
    }

    public function searchPickups()
    {
        $query = trim((string) $this->request->getGet('q'));
        $excludeDeliveryId = (int) ($this->request->getGet('exclude_delivery_id') ?? 0);
        $rows = $this->searchOpenPickupBalances($query, $excludeDeliveryId > 0 ? $excludeDeliveryId : null);

        return $this->response->setJSON(['results' => $rows]);
    }

    public function create()
    {
        $postedClientId = (int) $this->request->getPost('client_id');

        $rules = [
            'client_id' => 'required|is_natural_no_zero',
            'dr_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
            'payment_term' => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return $this->createFormWithErrors($this->validator, [], $postedClientId > 0 ? $postedClientId : null);
        }

        $clientId = (int) $this->request->getPost('client_id');
        $drNo = trim((string) $this->request->getPost('dr_no'));
        $date = (string) $this->request->getPost('date');
        $postedPaymentTerm = trim((string) $this->request->getPost('payment_term'));
        $items = $this->request->getPost('items');

        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);
        if (! $client) {
            return $this->createFormWithErrors(null, ['Client not found.'], $clientId);
        }

        if (! is_array($items) || count($items) === 0) {
            return $this->createFormWithErrors(null, ['At least one item is required.'], $clientId);
        }

        $deliveryModel = new DeliveryModel();
        $existing = $deliveryModel
            ->where('client_id', $clientId)
            ->where('dr_no', $drNo)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'DR number already exists for this client.');
        }

        $cleanItems = [];
        $total = 0;

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $lineTotal = $qty * $unitPrice;
            $total += $lineTotal;

            $cleanItems[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        if (empty($cleanItems)) {
            return $this->createFormWithErrors(null, ['Add at least one valid item with quantity.'], $clientId);
        }

        try {
            $pickupAllocation = $this->buildPickupAllocationFromPost($cleanItems);
        } catch (RuntimeException $e) {
            return $this->createFormWithErrors(null, [$e->getMessage()], $clientId);
        }

        $ledgerModel = new LedgerModel();
        $previousBalance = $this->latestLedgerBalance($clientId);

        $creditLimit = $client['credit_limit'] ?? null;
        if ($creditLimit !== null && $creditLimit !== '' && (float) $creditLimit > 0) {
            $projectedBalance = $previousBalance + $total;
            if ($projectedBalance > (float) $creditLimit) {
                return $this->createFormWithErrors(
                    null,
                    [
                        'Credit limit exceeded. Projected balance ' . number_format($projectedBalance, 2)
                            . ' is greater than client credit limit ' . number_format((float) $creditLimit, 2) . '.',
                    ],
                    $clientId
                );
            }
        }

        $effectivePaymentTerm = $postedPaymentTerm === ''
            ? (int) ($client['payment_term'] ?? 0)
            : (int) $postedPaymentTerm;
        $effectivePaymentTerm = max(0, $effectivePaymentTerm);
        $dueDate = $this->calculateDueDate($date, $effectivePaymentTerm);

        $db = db_connect();
        $db->transStart();

        $deliveryId = $deliveryModel->insert([
            'client_id' => $clientId,
            'dr_no' => $drNo,
            'date' => $date,
            'payment_term' => $effectivePaymentTerm,
            'due_date' => $dueDate,
            'total_amount' => $total,
            'status' => 'active',
        ], true);

        $deliveryItemModel = new DeliveryItemModel();
        foreach ($cleanItems as $index => $item) {
            $cleanItems[$index]['delivery_id'] = $deliveryId;
        }
        $deliveryItemModel->insertBatch($cleanItems);
        $this->replacePickupAllocationRows($deliveryId, $pickupAllocation);

        $firstItem = $cleanItems[0] ?? null;

        $ledgerModel->insert([
            'client_id' => $clientId,
            'entry_date' => $date,
            'dr_no' => $drNo,
            'pr_no' => null,
            'qty' => $firstItem['qty'] ?? null,
            'price' => $firstItem['unit_price'] ?? null,
            'amount' => $total,
            'collection' => 0,
            'balance' => $previousBalance + $total,
            'delivery_id' => $deliveryId,
            'payment_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to save delivery.');
        }

        $query = http_build_query([
            'dr_no' => $drNo,
            'from_date' => $date,
            'to_date' => $date,
        ]);

        return redirect()->to('clients/' . $postedClientId . '/deliveries?' . $query)->with('success', 'Delivery saved.');
    }

    public function update(int $deliveryId)
    {
        $delivery = $this->fetchDeliveryWithBalance($deliveryId);
        if (! $delivery) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! empty($delivery['voided_at']) || ($delivery['status'] ?? '') === 'voided') {
            return redirect()->back()->withInput()->with('error', 'Voided deliveries cannot be edited.');
        }

        if ((float) ($delivery['allocated_amount'] ?? 0) > 0) {
            return redirect()->back()->withInput()->with('error', 'Deliveries with payments cannot be edited.');
        }

        $rules = [
            'dr_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
            'payment_term' => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the delivery form and try again.');
        }

        $clientId = (int) $delivery['client_id'];
        $drNo = trim((string) $this->request->getPost('dr_no'));
        $date = (string) $this->request->getPost('date');
        $postedPaymentTerm = trim((string) $this->request->getPost('payment_term'));
        $items = $this->request->getPost('items');

        $deliveryModel = new DeliveryModel();
        $existing = $deliveryModel
            ->where('client_id', $clientId)
            ->where('dr_no', $drNo)
            ->where('id !=', $deliveryId)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'DR number already exists for this client.');
        }

        if (! is_array($items) || count($items) === 0) {
            return redirect()->back()->withInput()->with('error', 'At least one item is required.');
        }

        $cleanItems = $this->cleanDeliveryItems($items);
        if (empty($cleanItems)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one valid item with quantity.');
        }

        try {
            $pickupAllocation = $this->buildPickupAllocationFromPost($cleanItems, $deliveryId);
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $total = 0.0;
        foreach ($cleanItems as $item) {
            $total += (float) $item['line_total'];
        }

        $effectivePaymentTerm = $postedPaymentTerm === ''
            ? (int) ($delivery['payment_term'] ?? 0)
            : (int) $postedPaymentTerm;
        $effectivePaymentTerm = max(0, $effectivePaymentTerm);
        $dueDate = $this->calculateDueDate($date, $effectivePaymentTerm);

        $oldItems = $this->fetchDeliveryItems($deliveryId);
        $oldDelivery = $delivery;
        $oldTotal = (float) ($oldDelivery['total_amount'] ?? 0);

        $newDelivery = [
            'client_id' => $clientId,
            'dr_no' => $drNo,
            'date' => $date,
            'payment_term' => $effectivePaymentTerm,
            'due_date' => $dueDate,
            'total_amount' => $total,
            'status' => $delivery['status'] ?? 'active',
        ];

        $db = db_connect();
        $db->transStart();

        $deliveryModel->update($deliveryId, $newDelivery);

        $itemModel = new DeliveryItemModel();
        $itemModel->where('delivery_id', $deliveryId)->delete();
        foreach ($cleanItems as $index => $item) {
            $cleanItems[$index]['delivery_id'] = $deliveryId;
        }
        $itemModel->insertBatch($cleanItems);
        $this->replacePickupAllocationRows($deliveryId, $pickupAllocation);

        $difference = round($total - $oldTotal, 2);
        if (abs($difference) > 0.005) {
            $this->insertDeliveryAdjustmentLedgerRow($clientId, $deliveryId, $drNo, $difference);
        }

        $freshDelivery = $this->fetchDeliveryWithBalance($deliveryId) ?? array_merge($oldDelivery, $newDelivery);
        $freshItems = $this->fetchDeliveryItems($deliveryId);
        (new DeliveryHistoryService())->record(
            $deliveryId,
            (int) (session('user_id') ?? 0) ?: null,
            'edit',
            $oldDelivery,
            $oldItems,
            $freshDelivery,
            $freshItems,
            $this->buildDeliveryChangeSummary($oldDelivery, $freshDelivery, $oldItems, $freshItems)
        );

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to update delivery.');
        }

        return redirect()->back()->with('success', 'Delivery updated.');
    }

    public function void(int $deliveryId)
    {
        $delivery = $this->fetchDeliveryWithBalance($deliveryId);
        if (! $delivery) {
            throw PageNotFoundException::forPageNotFound();
        }

        $reason = trim((string) $this->request->getPost('void_reason'));
        if ($reason === '') {
            return redirect()->back()->withInput()->with('error', 'Reason for voiding is required.');
        }

        if (! empty($delivery['voided_at']) || ($delivery['status'] ?? '') === 'voided') {
            return redirect()->back()->with('error', 'Delivery is already voided.');
        }

        if ((float) ($delivery['allocated_amount'] ?? 0) > 0) {
            return redirect()->back()->with('error', 'Deliveries with partial or full payments cannot be voided.');
        }

        if ((float) ($delivery['balance'] ?? 0) <= 0) {
            return redirect()->back()->with('error', 'Deliveries with zero balance cannot be voided.');
        }

        $oldItems = $this->fetchDeliveryItems($deliveryId);
        $voidedAt = date('Y-m-d H:i:s');

        $db = db_connect();
        $db->transStart();

        (new DeliveryModel())->update($deliveryId, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => $voidedAt,
        ]);

        (new DeliveryPickupAllocationModel())->where('delivery_id', $deliveryId)->delete();
        $this->insertVoidLedgerRow($delivery, $voidedAt);

        $newDelivery = $this->fetchDeliveryWithBalance($deliveryId) ?? array_merge($delivery, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => $voidedAt,
        ]);

        (new DeliveryHistoryService())->record(
            $deliveryId,
            (int) (session('user_id') ?? 0) ?: null,
            'void',
            $delivery,
            $oldItems,
            $newDelivery,
            $oldItems,
            'Voided delivery. Reason: ' . $reason
        );

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Failed to void delivery.');
        }

        return redirect()->back()->with('success', 'Delivery voided.');
    }

    private function searchOpenPickupBalances(string $query, ?int $excludeDeliveryId = null): array
    {
        $db = db_connect();
        $allocationBuilder = $db->table('delivery_pickup_allocations dpa')
            ->select('dpa.purchase_order_id, dpa.product_id, SUM(dpa.qty_allocated) as qty_allocated')
            ->join('deliveries d', 'd.id = dpa.delivery_id', 'left')
            ->where('d.voided_at', null);

        if ($excludeDeliveryId !== null) {
            $allocationBuilder->where('dpa.delivery_id !=', $excludeDeliveryId);
        }

        $allocationSql = $allocationBuilder
            ->groupBy('dpa.purchase_order_id, dpa.product_id')
            ->getCompiledSelect();

        $builder = $db->table('purchase_orders po')
            ->select('po.id as purchase_order_id, po.po_no as rr_no, po.date as rr_date')
            ->select('po.supplier_id, suppliers.name as supplier_name')
            ->select('poi.product_id, products.product_name')
            ->select('SUM(poi.qty) as qty')
            ->select('MIN(poi.unit_price) as unit_price')
            ->select('SUM(poi.line_total) as amount')
            ->select('COALESCE(alloc.qty_allocated, 0) as delivered_qty')
            ->select('(SUM(poi.qty) - COALESCE(alloc.qty_allocated, 0)) as remaining_qty')
            ->join('purchase_order_items poi', 'poi.purchase_order_id = po.id', 'inner')
            ->join('suppliers', 'suppliers.id = po.supplier_id', 'left')
            ->join('products', 'products.id = poi.product_id', 'left')
            ->join("({$allocationSql}) alloc", 'alloc.purchase_order_id = po.id AND alloc.product_id = poi.product_id', 'left')
            ->where('po.voided_at', null)
            ->where('po.status', 'active');

        if ($query !== '') {
            $builder->groupStart()
                ->like('po.po_no', $query)
                ->orLike('suppliers.name', $query)
                ->orLike('products.product_name', $query)
                ->groupEnd();
        }

        $rows = $builder
            ->groupBy('po.id, po.po_no, po.date, po.supplier_id, suppliers.name, poi.product_id, products.product_name, alloc.qty_allocated')
            ->having('remaining_qty >', 0.000005)
            ->orderBy('po.date', 'desc')
            ->orderBy('po.id', 'desc')
            ->limit(10)
            ->get()
            ->getResultArray();

        foreach ($rows as $index => $row) {
            foreach (['qty', 'unit_price', 'amount', 'delivered_qty', 'remaining_qty'] as $field) {
                $rows[$index][$field] = (float) ($row[$field] ?? 0);
            }
        }

        return $rows;
    }

    private function buildPickupAllocationFromPost(array $deliveryItems, ?int $excludeDeliveryId = null): array
    {
        $pickupId = (int) ($this->request->getPost('pickup_id') ?? 0);
        if ($pickupId <= 0) {
            return [];
        }

        $pickup = (new PurchaseOrderModel())->find($pickupId);
        if (! $pickup || ! empty($pickup['voided_at']) || ($pickup['status'] ?? '') !== 'active') {
            throw new RuntimeException('Selected RR / pickup was not found or is not active.');
        }

        $productId = (int) ($this->request->getPost('pickup_product_id') ?? 0);
        $pickupProducts = $this->pickupProductQuantities($pickupId);
        if ($productId <= 0 && count($pickupProducts) === 1) {
            $productId = (int) array_key_first($pickupProducts);
        }

        if ($productId <= 0 || ! isset($pickupProducts[$productId])) {
            throw new RuntimeException('Selected RR / pickup product does not match the delivery.');
        }

        $deliveryQty = $this->deliveryQtyForProduct($deliveryItems, $productId);
        if ($deliveryQty <= 0.000005) {
            throw new RuntimeException('Selected RR / pickup was chosen, but the delivery has no matching product quantity.');
        }

        $remainingQty = $this->pickupRemainingQty($pickupId, $productId, $excludeDeliveryId);
        if ($deliveryQty > $remainingQty + 0.000005) {
            throw new RuntimeException(
                'Delivery quantity exceeds selected RR remaining quantity. Remaining: ' . number_format($remainingQty, 5)
            );
        }

        return [[
            'purchase_order_id' => $pickupId,
            'product_id' => $productId,
            'qty_allocated' => $deliveryQty,
            'created_at' => date('Y-m-d H:i:s'),
        ]];
    }

    private function pickupProductQuantities(int $pickupId): array
    {
        $rows = (new PurchaseOrderItemModel())
            ->select('product_id, SUM(qty) as qty')
            ->where('purchase_order_id', $pickupId)
            ->groupBy('product_id')
            ->findAll();

        $quantities = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId > 0) {
                $quantities[$productId] = (float) ($row['qty'] ?? 0);
            }
        }

        return $quantities;
    }

    private function pickupRemainingQty(int $pickupId, int $productId, ?int $excludeDeliveryId = null): float
    {
        $picked = (new PurchaseOrderItemModel())
            ->select('SUM(qty) as qty')
            ->where('purchase_order_id', $pickupId)
            ->where('product_id', $productId)
            ->first();

        $allocationModel = new DeliveryPickupAllocationModel();
        $allocationModel
            ->select('SUM(delivery_pickup_allocations.qty_allocated) as qty_allocated')
            ->join('deliveries', 'deliveries.id = delivery_pickup_allocations.delivery_id', 'left')
            ->where('delivery_pickup_allocations.purchase_order_id', $pickupId)
            ->where('delivery_pickup_allocations.product_id', $productId)
            ->where('deliveries.voided_at', null);

        if ($excludeDeliveryId !== null) {
            $allocationModel->where('delivery_pickup_allocations.delivery_id !=', $excludeDeliveryId);
        }

        $allocated = $allocationModel->first();

        return max(0.0, (float) ($picked['qty'] ?? 0) - (float) ($allocated['qty_allocated'] ?? 0));
    }

    private function deliveryQtyForProduct(array $deliveryItems, int $productId): float
    {
        $qty = 0.0;
        foreach ($deliveryItems as $item) {
            if ((int) ($item['product_id'] ?? 0) === $productId) {
                $qty += (float) ($item['qty'] ?? 0);
            }
        }

        return $qty;
    }

    private function replacePickupAllocationRows(int $deliveryId, array $allocations): void
    {
        $model = new DeliveryPickupAllocationModel();
        $model->where('delivery_id', $deliveryId)->delete();

        if (empty($allocations)) {
            return;
        }

        foreach ($allocations as $index => $allocation) {
            $allocations[$index]['delivery_id'] = $deliveryId;
        }

        $model->insertBatch($allocations);
    }

    private function createFormWithErrors($validation = null, array $errors = [], $clientId = null)
    {
        if ($this->request->getHeaderLine('Referer') !== '') {
            $message = ! empty($errors) ? implode(' ', $errors) : 'Please check the delivery form and try again.';

            return redirect()->back()
                ->withInput()
                ->with('error', $message);
        }

        return view('deliveries/form', $this->buildFormData($clientId, $validation, $errors));
    }

    private function cleanDeliveryItems(array $items): array
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

    private function fetchDeliveryWithBalance(int $deliveryId): ?array
    {
        $row = db_connect()->table('deliveries d')
            ->select('d.*')
            ->select('c.name as client_name')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(d.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('clients c', 'c.id = d.client_id', 'left')
            ->join('payment_allocations pa', 'pa.delivery_id = d.id', 'left')
            ->join('payments p', 'p.id = pa.payment_id', 'left')
            ->where('d.id', $deliveryId)
            ->groupBy('d.id')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function fetchDeliveryItems(int $deliveryId): array
    {
        return (new DeliveryItemModel())
            ->select('delivery_items.*, products.product_name')
            ->join('products', 'products.id = delivery_items.product_id', 'left')
            ->where('delivery_id', $deliveryId)
            ->orderBy('delivery_items.id', 'asc')
            ->findAll();
    }

    private function insertVoidLedgerRow(array $delivery, string $voidedAt): void
    {
        $clientId = (int) $delivery['client_id'];
        $amount = (float) $delivery['total_amount'];
        $previousBalance = $this->latestLedgerBalance($clientId);

        (new LedgerModel())->insert([
            'client_id' => $clientId,
            'entry_date' => date('Y-m-d', strtotime($voidedAt)),
            'dr_no' => (string) $delivery['dr_no'],
            'pr_no' => null,
            'qty' => null,
            'price' => null,
            'amount' => 0,
            'collection' => 0,
            'account_title' => 'Voided',
            'other_accounts' => $amount,
            'balance' => $previousBalance - $amount,
            'delivery_id' => (int) $delivery['id'],
            'payment_id' => null,
            'created_at' => $voidedAt,
        ]);
    }

    private function insertDeliveryAdjustmentLedgerRow(int $clientId, int $deliveryId, string $drNo, float $difference): void
    {
        $previousBalance = $this->latestLedgerBalance($clientId);
        $amount = $difference > 0 ? $difference : 0;
        $otherAccounts = $difference < 0 ? abs($difference) : 0;

        (new LedgerModel())->insert([
            'client_id' => $clientId,
            'entry_date' => date('Y-m-d'),
            'dr_no' => $drNo,
            'pr_no' => null,
            'qty' => null,
            'price' => null,
            'amount' => $amount,
            'collection' => 0,
            'account_title' => 'Delivery Adjustment',
            'other_accounts' => $otherAccounts,
            'balance' => $previousBalance + $amount - $otherAccounts,
            'delivery_id' => $deliveryId,
            'payment_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function latestLedgerBalance(int $clientId): float
    {
        $lastLedger = (new LedgerModel())
            ->select('balance')
            ->where('client_id', $clientId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLedger) {
            return (float) ($lastLedger['balance'] ?? 0);
        }

        $client = (new ClientModel())
            ->select('forwarded_balance')
            ->find($clientId);

        return (float) ($client['forwarded_balance'] ?? 0);
    }

    private function buildDeliveryChangeSummary(array $oldDelivery, array $newDelivery, array $oldItems, array $newItems): string
    {
        $changes = [];
        foreach (['dr_no' => 'DR#', 'date' => 'Date', 'due_date' => 'Due date', 'payment_term' => 'Term'] as $field => $label) {
            if ((string) ($oldDelivery[$field] ?? '') !== (string) ($newDelivery[$field] ?? '')) {
                $changes[] = $label . ' changed from ' . ($oldDelivery[$field] ?? '-') . ' to ' . ($newDelivery[$field] ?? '-');
            }
        }

        $oldTotal = (float) ($oldDelivery['total_amount'] ?? 0);
        $newTotal = (float) ($newDelivery['total_amount'] ?? 0);
        if (abs($oldTotal - $newTotal) > 0.005) {
            $changes[] = 'Total changed from ' . number_format($oldTotal, 2) . ' to ' . number_format($newTotal, 2);
        }

        if (count($oldItems) !== count($newItems)) {
            $changes[] = 'Item count changed from ' . count($oldItems) . ' to ' . count($newItems);
        }

        return empty($changes) ? 'Delivery details updated.' : implode('; ', $changes) . '.';
    }

    private function buildFormData($clientId = null, $validation = null, array $errors = [], bool $embeddedForm = false): array
    {
        $clientModel = new ClientModel();
        $productModel = new ProductModel();

        $products = $productModel->orderBy('product_name', 'asc')->findAll();
        $productsJson = json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $clients = $clientModel->orderBy('name', 'asc')->findAll();
        $clientPriceMap = $this->buildClientPriceMap();

        $selectedClient = null;
        $defaultPaymentTerm = old('payment_term');
        if ($clientId) {
            $selectedClient = $clientModel->find((int) $clientId);
            if ($selectedClient && ($defaultPaymentTerm === null || $defaultPaymentTerm === '')) {
                $defaultPaymentTerm = $selectedClient['payment_term'] ?? '';
            }
        }

        return [
            'title' => 'New Delivery',
            'action' => base_url('deliveries'),
            'clientId' => $clientId,
            'selectedClient' => $selectedClient,
            'clients' => $clients,
            'clientsJson' => json_encode($clients, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            'defaultPaymentTerm' => $defaultPaymentTerm,
            'products' => $products,
            'productsJson' => $productsJson,
            'clientPriceMap' => $clientPriceMap,
            'clientPriceMapJson' => json_encode($clientPriceMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            'validation' => $validation,
            'extraErrors' => $errors,
            'embeddedForm' => $embeddedForm,
        ];
    }

    private function buildQuickPayData(): array
    {
        $userId = (int) (session('user_id') ?? 0);
        $assignedUser = $userId > 0 ? (new UserModel())->find($userId) : null;
        $activeRange = (new PaymentPostingService())->getActiveReceiptRange($userId);

        return [
            'assignedUser' => $assignedUser,
            'activeReceipt' => $activeRange ? (int) $activeRange['next_no'] : null,
            'rangeEnd' => $activeRange ? (int) $activeRange['end_no'] : null,
            'banks' => (new BankModel())->orderBy('bank_name', 'asc')->findAll(),
        ];
    }

    private function buildDeliveryActionData(): array
    {
        return [
            'products' => (new ProductModel())->orderBy('product_name', 'asc')->findAll(),
            'clientPriceMap' => $this->buildClientPriceMap(),
        ];
    }

    private function buildClientPriceMap(): array
    {
        $rows = (new ProductClientPriceModel())
            ->select('client_id, product_id, price')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $clientId = (string) ($row['client_id'] ?? '');
            $productId = (string) ($row['product_id'] ?? '');
            if ($clientId === '' || $productId === '') {
                continue;
            }

            $map[$clientId][$productId] = $row['price'];
        }

        return $map;
    }

    private function resolveDateRange(): array
    {
        $fromDate = trim((string) ($this->request->getGet('from_date') ?? ''));
        $toDate = trim((string) ($this->request->getGet('to_date') ?? ''));

        return [$fromDate, $toDate];
    }

    private function resolveDrNoFilter(): string
    {
        return trim((string) ($this->request->getGet('dr_no') ?? ''));
    }

    private function applyDeliveryFilters($builder, ?int $clientId, string $fromDate, string $toDate, string $drNo)
    {
        $builder->where('d.voided_at', null);

        if ($clientId !== null) {
            $builder->where('d.client_id', $clientId);
        }

        if ($drNo === '' && $fromDate !== '') {
            $builder->where('d.date >=', $fromDate);
        }

        if ($drNo === '' && $toDate !== '') {
            $builder->where('d.date <=', $toDate);
        }

        if ($drNo !== '') {
            $builder->like('d.dr_no', $drNo);
        }

        return $builder;
    }

    private function fetchDeliveries(?int $clientId, string $fromDate, string $toDate, string $drNo = '', bool $paginate = false): array
    {
        $db = db_connect();
        $builder = $db->table('deliveries d');
        $builder
            ->select('d.id, d.client_id, d.dr_no, d.date, d.payment_term, d.due_date, d.total_amount, d.status, d.void_reason, d.voided_at')
            ->select('c.name as client_name')
            ->select('c.payment_term as client_payment_term')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(d.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('clients c', 'c.id = d.client_id', 'left')
            ->join('payment_allocations pa', 'pa.delivery_id = d.id', 'left')
            ->join('payments p', 'p.id = pa.payment_id', 'left');

        $this->applyDeliveryFilters($builder, $clientId, $fromDate, $toDate, $drNo);

        $totalBuilder = $db->table('deliveries d')
            ->select('COALESCE(SUM(d.total_amount), 0) as total_amount')
            ->select('COALESCE(SUM(d.total_amount - COALESCE(payments_summary.allocated_amount, 0)), 0) as total_balance')
            ->join(
                "(SELECT pa.delivery_id, SUM(pa.amount) as allocated_amount FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE p.status = 'posted' GROUP BY pa.delivery_id) payments_summary",
                'payments_summary.delivery_id = d.id',
                'left'
            );
        $this->applyDeliveryFilters($totalBuilder, $clientId, $fromDate, $toDate, $drNo);
        $totals = $totalBuilder->get()->getRowArray() ?? [];

        $pagerLinks = '';
        $rowOffset = 0;
        if ($paginate) {
            $perPage = 20;
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));

            $countBuilder = $db->table('deliveries d')->select('COUNT(*) as total');
            $this->applyDeliveryFilters($countBuilder, $clientId, $fromDate, $toDate, $drNo);
            $totalRows = (int) (($countBuilder->get()->getRowArray()['total'] ?? 0));
            $rowOffset = ($page - 1) * $perPage;

            $pagerLinks = service('pager')->makeLinks($page, $perPage, $totalRows, 'default_full');
            $builder->limit($perPage, $rowOffset);
        }

        $deliveries = $builder
            ->groupBy('d.id')
            ->orderBy('d.date', 'desc')
            ->orderBy('d.id', 'desc')
            ->get()
            ->getResultArray();

        $deliveryIds = array_filter(array_map('intval', array_column($deliveries, 'id')));
        $itemsByDelivery = [];
        $allocationsByDelivery = [];
        $pickupAllocationsByDelivery = [];
        $historiesByDelivery = [];

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
            }

            $allocations = $db->table('payment_allocations pa')
                ->select('pa.delivery_id, pa.amount, p.pr_no, p.date')
                ->join('payments p', 'p.id = pa.payment_id', 'left')
                ->where('p.status', 'posted')
                ->whereIn('pa.delivery_id', $deliveryIds)
                ->orderBy('p.date', 'asc')
                ->get()
                ->getResultArray();

            foreach ($allocations as $allocation) {
                $deliveryId = (int) $allocation['delivery_id'];
                $allocationsByDelivery[$deliveryId][] = $allocation;
            }

            $pickupAllocations = $db->table('delivery_pickup_allocations dpa')
                ->select('dpa.delivery_id, dpa.purchase_order_id, dpa.product_id, dpa.qty_allocated')
                ->select('po.po_no as rr_no, po.date as rr_date')
                ->select('suppliers.name as supplier_name, products.product_name')
                ->select(
                    '((SELECT COALESCE(SUM(poi.qty), 0) FROM purchase_order_items poi WHERE poi.purchase_order_id = dpa.purchase_order_id AND poi.product_id = dpa.product_id) - '
                        . '(SELECT COALESCE(SUM(dpa2.qty_allocated), 0) FROM delivery_pickup_allocations dpa2 LEFT JOIN deliveries d2 ON d2.id = dpa2.delivery_id WHERE dpa2.purchase_order_id = dpa.purchase_order_id AND dpa2.product_id = dpa.product_id AND d2.voided_at IS NULL AND dpa2.delivery_id != dpa.delivery_id)) as remaining_qty',
                    false
                )
                ->join('purchase_orders po', 'po.id = dpa.purchase_order_id', 'left')
                ->join('suppliers', 'suppliers.id = po.supplier_id', 'left')
                ->join('products', 'products.id = dpa.product_id', 'left')
                ->whereIn('dpa.delivery_id', $deliveryIds)
                ->orderBy('po.date', 'asc')
                ->orderBy('po.id', 'asc')
                ->get()
                ->getResultArray();

            foreach ($pickupAllocations as $allocation) {
                $deliveryId = (int) $allocation['delivery_id'];
                $pickupAllocationsByDelivery[$deliveryId][] = $allocation;
            }

            if ($db->tableExists('delivery_histories')) {
                $histories = (new DeliveryHistoryModel())
                    ->select('delivery_histories.*, users.name as editor_name, users.username as editor_username')
                    ->join('users', 'users.id = delivery_histories.edited_by', 'left')
                    ->whereIn('delivery_id', $deliveryIds)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->findAll();

                foreach ($histories as $history) {
                    $deliveryId = (int) $history['delivery_id'];
                    $historiesByDelivery[$deliveryId][] = $history;
                }
            }
        }

        return [
            'deliveries' => $deliveries,
            'itemsByDelivery' => $itemsByDelivery,
            'allocationsByDelivery' => $allocationsByDelivery,
            'pickupAllocationsByDelivery' => $pickupAllocationsByDelivery,
            'historiesByDelivery' => $historiesByDelivery,
            'totalAmount' => (float) ($totals['total_amount'] ?? 0),
            'totalBalance' => (float) ($totals['total_balance'] ?? 0),
            'pagerLinks' => $pagerLinks,
            'rowOffset' => $rowOffset,
        ];
    }

    private function calculateDueDate(string $deliveryDate, int $paymentTerm): string
    {
        $timestamp = strtotime($deliveryDate);
        if ($timestamp === false) {
            return $deliveryDate;
        }

        return date('Y-m-d', strtotime('+' . $paymentTerm . ' days', $timestamp));
    }
}
