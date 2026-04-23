<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\DeliveryItemModel;
use App\Models\DeliveryModel;
use App\Models\LedgerModel;
use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class Deliveries extends BaseController
{
    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $result = $this->fetchDeliveries(null, $fromDate, $toDate);

        return view('deliveries/index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'deliveries' => $result['deliveries'],
            'itemsByDelivery' => $result['itemsByDelivery'],
            'allocationsByDelivery' => $result['allocationsByDelivery'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
        ]);
    }

    public function clientList(int $clientId): string
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);
        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $result = $this->fetchDeliveries($clientId, $fromDate, $toDate);

        return view('deliveries/list', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'deliveries' => $result['deliveries'],
            'itemsByDelivery' => $result['itemsByDelivery'],
            'allocationsByDelivery' => $result['allocationsByDelivery'],
            'totalAmount' => $result['totalAmount'],
            'totalBalance' => $result['totalBalance'],
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
        $result = $this->fetchDeliveries($clientId, $fromDate, $toDate);

        $html = view('deliveries/listprint', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
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
        $clientModel = new ClientModel();
        $productModel = new ProductModel();

        $products = $productModel->orderBy('product_name', 'asc')->findAll();
        $productsJson = json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $selectedClient = null;
        if ($clientId) {
            $selectedClient = $clientModel->find((int) $clientId);
            if (! $selectedClient) {
                return redirect()->to('/deliveries')->with('error', 'Client not found.');
            }
        }

        return view('deliveries/form', [
            'title' => 'New Delivery',
            'action' => base_url('deliveries'),
            'clientId' => $clientId,
            'selectedClient' => $selectedClient,
            'clients' => $clientModel->orderBy('name', 'asc')->findAll(),
            'products' => $products,
            'productsJson' => $productsJson,
        ]);
    }

    public function create()
    {
        $postedClientId = (int) $this->request->getPost('client_id');

        $rules = [
            'client_id' => 'required|is_natural_no_zero',
            'dr_no' => 'required|max_length[50]',
            'date' => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return $this->createFormWithErrors($this->validator, [], $postedClientId > 0 ? $postedClientId : null);
        }

        $clientId = (int) $this->request->getPost('client_id');
        $drNo = trim((string) $this->request->getPost('dr_no'));
        $date = (string) $this->request->getPost('date');
        $items = $this->request->getPost('items');

        if (! is_array($items) || count($items) === 0) {
            return $this->createFormWithErrors(null, ['At least one item is required.'], $clientId);
        }

        $deliveryModel = new DeliveryModel();
        $existing = $deliveryModel
            ->where('client_id', $clientId)
            ->where('dr_no', $drNo)
            ->first();

        if ($existing) {
            return $this->createFormWithErrors(null, ['DR number already exists for this client.'], $clientId);
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

        $db = db_connect();
        $db->transStart();

        $deliveryId = $deliveryModel->insert([
            'client_id' => $clientId,
            'dr_no' => $drNo,
            'date' => $date,
            'total_amount' => $total,
            'status' => 'open',
        ], true);

        $deliveryItemModel = new DeliveryItemModel();
        foreach ($cleanItems as $index => $item) {
            $cleanItems[$index]['delivery_id'] = $deliveryId;
        }
        $deliveryItemModel->insertBatch($cleanItems);

        $ledgerModel = new LedgerModel();
        $lastLedger = $ledgerModel
            ->select('balance')
            ->where('client_id', $clientId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $previousBalance = (float) ($lastLedger['balance'] ?? 0);
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

        return redirect()->to('clients/' . $postedClientId . '/deliveries')->with('success', 'Delivery saved.');
    }

    private function createFormWithErrors($validation = null, array $errors = [], $clientId = null)
    {
        $clientModel = new ClientModel();
        $productModel = new ProductModel();

        $products = $productModel->orderBy('product_name', 'asc')->findAll();
        $productsJson = json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $selectedClient = null;
        if ($clientId) {
            $selectedClient = $clientModel->find((int) $clientId);
        }

        return view('deliveries/form', [
            'title' => 'New Delivery',
            'action' => base_url('deliveries'),
            'clientId' => $clientId,
            'selectedClient' => $selectedClient,
            'clients' => $clientModel->orderBy('name', 'asc')->findAll(),
            'products' => $products,
            'productsJson' => $productsJson,
            'validation' => $validation,
            'extraErrors' => $errors,
        ]);
    }

    private function resolveDateRange(): array
    {
        $fromDate = trim((string) ($this->request->getGet('from_date') ?? ''));
        $toDate = trim((string) ($this->request->getGet('to_date') ?? ''));

        if ($fromDate === '' && $toDate === '') {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
        }

        return [$fromDate, $toDate];
    }

    private function fetchDeliveries(?int $clientId, string $fromDate, string $toDate): array
    {
        $db = db_connect();
        $builder = $db->table('deliveries d');
        $builder
            ->select('d.id, d.client_id, d.dr_no, d.date, d.total_amount')
            ->select('c.name as client_name')
            ->select("COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0) as allocated_amount")
            ->select("(d.total_amount - COALESCE(SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END), 0)) as balance")
            ->join('clients c', 'c.id = d.client_id', 'left')
            ->join('payment_allocations pa', 'pa.delivery_id = d.id', 'left')
            ->join('payments p', 'p.id = pa.payment_id', 'left')
            ->where('d.voided_at', null);

        if ($clientId !== null) {
            $builder->where('d.client_id', $clientId);
        }

        if ($fromDate !== '') {
            $builder->where('d.date >=', $fromDate);
        }

        if ($toDate !== '') {
            $builder->where('d.date <=', $toDate);
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
        }

        $totalAmount = 0.0;
        $totalBalance = 0.0;
        foreach ($deliveries as $delivery) {
            $totalAmount += (float) $delivery['total_amount'];
            $totalBalance += (float) $delivery['balance'];
        }

        return [
            'deliveries' => $deliveries,
            'itemsByDelivery' => $itemsByDelivery,
            'allocationsByDelivery' => $allocationsByDelivery,
            'totalAmount' => $totalAmount,
            'totalBalance' => $totalBalance,
        ];
    }
}
