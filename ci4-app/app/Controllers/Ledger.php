<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\DeliveryItemModel;
use App\Models\LedgerModel;

class Ledger extends BaseController
{
    public function index(): string
    {
        $clientModel = new ClientModel();
        $clients = $clientModel->orderBy('name', 'asc')->findAll();

        $clientId = (int) $this->request->getGet('client_id');
        $start = (string) $this->request->getGet('start');
        $end = (string) $this->request->getGet('end');

        $selectedClient = null;
        $openingBalance = 0.0;
        $rows = [];
        $itemsByDelivery = [];
        $itemCounts = [];
        $allocationsByDelivery = [];
        $allocationsByPayment = [];

        if ($clientId > 0) {
            $selectedClient = $clientModel->find($clientId);
        }

        if ($selectedClient) {
            $ledgerModel = new LedgerModel();

            if ($start !== '') {
                $openingRow = $ledgerModel
                    ->select('balance')
                    ->where('client_id', $clientId)
                    ->where('entry_date <', $start)
                    ->orderBy('entry_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $openingBalance = (float) ($openingRow['balance'] ?? 0);
            }

            $builder = $ledgerModel->where('client_id', $clientId);

            if ($start !== '') {
                $builder->where('entry_date >=', $start);
            }

            if ($end !== '') {
                $builder->where('entry_date <=', $end);
            }

            $rows = $builder
                ->orderBy('entry_date', 'asc')
                ->orderBy('id', 'asc')
                ->findAll();

            $deliveryIds = array_filter(array_column($rows, 'delivery_id'));
            $paymentIds = array_filter(array_column($rows, 'payment_id'));

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
                    $itemCounts[$deliveryId] = ($itemCounts[$deliveryId] ?? 0) + 1;
                }
            }

            if (! empty($deliveryIds)) {
                $db = db_connect();
                $deliveryAllocations = $db->table('payment_allocations pa')
                    ->select('pa.delivery_id, pa.amount, p.pr_no, p.date')
                    ->join('payments p', 'p.id = pa.payment_id', 'left')
                    ->whereIn('pa.delivery_id', $deliveryIds)
                    ->orderBy('p.date', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($deliveryAllocations as $allocation) {
                    $deliveryId = (int) $allocation['delivery_id'];
                    $allocationsByDelivery[$deliveryId][] = $allocation;
                }
            }

            if (! empty($paymentIds)) {
                $db = db_connect();
                $paymentAllocations = $db->table('payment_allocations pa')
                    ->select('pa.payment_id, pa.amount, d.dr_no, d.date')
                    ->join('deliveries d', 'd.id = pa.delivery_id', 'left')
                    ->whereIn('pa.payment_id', $paymentIds)
                    ->orderBy('d.date', 'asc')
                    ->get()
                    ->getResultArray();

                foreach ($paymentAllocations as $allocation) {
                    $paymentId = (int) $allocation['payment_id'];
                    $allocationsByPayment[$paymentId][] = $allocation;
                }
            }
        }

        return view('ledger/index', [
            'clients' => $clients,
            'selectedClient' => $selectedClient,
            'clientId' => $clientId,
            'start' => $start,
            'end' => $end,
            'openingBalance' => $openingBalance,
            'rows' => $rows,
            'itemsByDelivery' => $itemsByDelivery,
            'itemCounts' => $itemCounts,
            'allocationsByDelivery' => $allocationsByDelivery,
            'allocationsByPayment' => $allocationsByPayment,
        ]);
    }
}
