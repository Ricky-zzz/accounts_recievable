<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\DeliveryItemModel;
use App\Models\DeliveryModel;
use App\Models\LedgerModel;
use App\Models\ProductModel;

class Deliveries extends BaseController
{
    public function index(): string
    {
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $clients = [];

        if ($query !== '') {
            $clientModel = new ClientModel();
            $clients = $clientModel
                ->like('name', $query)
                ->orderBy('name', 'asc')
                ->findAll();
        }

        return view('deliveries/index', [
            'query' => $query,
            'clients' => $clients,
        ]);
    }

    public function list(): string
    {
        $fromDate = trim((string) ($this->request->getGet('from_date') ?? ''));
        $toDate = trim((string) ($this->request->getGet('to_date') ?? ''));
        $deliveries = [];

        // Default to today's date
        if ($fromDate === '' && $toDate === '') {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
        }

        if ($fromDate !== '' || $toDate !== '') {
            $db = db_connect();
            $builder = $db->table('deliveries');
            $builder
                ->select('deliveries.*, clients.name as client_name')
                ->join('clients', 'clients.id = deliveries.client_id', 'left');

            if ($fromDate !== '') {
                $builder->where('deliveries.date >=', $fromDate);
            }
            if ($toDate !== '') {
                $builder->where('deliveries.date <=', $toDate);
            }

            $builder->orderBy('deliveries.date', 'desc')->orderBy('deliveries.id', 'desc');
            $deliveries = $builder->get()->getResultArray();
        }

        return view('deliveries/list', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'deliveries' => $deliveries,
        ]);
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

        return redirect()->to('/deliveries')->with('success', 'Delivery saved.');
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
}
