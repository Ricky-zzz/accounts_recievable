<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\ClientModel;
use App\Models\DeliveryHistoryModel;
use App\Models\DeliveryItemModel;
use App\Models\DeliveryModel;
use App\Models\LedgerModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Services\DeliveryHistoryService;
use App\Services\PaymentPostingService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

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

        $ledgerModel = new LedgerModel();
        $lastLedger = $ledgerModel
            ->select('balance')
            ->where('client_id', $clientId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $previousBalance = (float) ($lastLedger['balance'] ?? 0);

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

        return (float) ($lastLedger['balance'] ?? 0);
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
        ];
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
