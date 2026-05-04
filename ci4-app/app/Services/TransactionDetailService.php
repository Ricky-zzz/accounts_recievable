<?php

namespace App\Services;

class TransactionDetailService
{
    public function delivery(int $deliveryId): ?array
    {
        $db = db_connect();
        $delivery = $db->table('deliveries d')
            ->select('d.*, clients.name as client_name')
            ->join('clients', 'clients.id = d.client_id', 'left')
            ->where('d.id', $deliveryId)
            ->get()
            ->getRowArray();

        if (! $delivery) {
            return null;
        }

        return [
            'delivery' => $delivery,
            'items' => $db->table('delivery_items di')
                ->select('di.*, products.product_name')
                ->join('products', 'products.id = di.product_id', 'left')
                ->where('di.delivery_id', $deliveryId)
                ->orderBy('di.id', 'asc')
                ->get()
                ->getResultArray(),
            'allocations' => $db->table('payment_allocations pa')
                ->select('pa.delivery_id, pa.amount, p.id as payment_id, p.pr_no, p.date')
                ->join('payments p', 'p.id = pa.payment_id', 'left')
                ->where('p.status', 'posted')
                ->where('pa.delivery_id', $deliveryId)
                ->orderBy('p.date', 'asc')
                ->orderBy('p.id', 'asc')
                ->get()
                ->getResultArray(),
            'pickup_allocations' => $this->deliveryPickupAllocations($deliveryId),
            'histories' => $this->deliveryHistories($deliveryId),
        ];
    }

    public function payment(int $paymentId): ?array
    {
        $db = db_connect();
        $payment = $db->table('payments p')
            ->select('p.*, clients.name as client_name')
            ->join('clients', 'clients.id = p.client_id', 'left')
            ->where('p.id', $paymentId)
            ->get()
            ->getRowArray();

        if (! $payment) {
            return null;
        }

        return [
            'payment' => $payment,
            'allocations' => $db->table('payment_allocations pa')
                ->select('pa.payment_id, pa.amount, d.id as delivery_id, d.dr_no, d.date')
                ->join('deliveries d', 'd.id = pa.delivery_id', 'left')
                ->where('pa.payment_id', $paymentId)
                ->orderBy('d.date', 'asc')
                ->orderBy('d.id', 'asc')
                ->get()
                ->getResultArray(),
            'other_accounts' => $db->table('boa b')
                ->select('b.payment_id, b.account_title, b.dr, b.ar_others, b.description, b.date, b.reference')
                ->where('b.payment_id', $paymentId)
                ->groupStart()
                    ->where('b.account_title IS NOT NULL', null, false)
                    ->orWhere('b.ar_others >', 0)
                ->groupEnd()
                ->orderBy('b.date', 'asc')
                ->orderBy('b.id', 'asc')
                ->get()
                ->getResultArray(),
        ];
    }

    public function purchaseOrder(int $purchaseOrderId): ?array
    {
        $db = db_connect();
        $purchaseOrder = $db->table('purchase_orders po')
            ->select('po.*, suppliers.name as supplier_name')
            ->join('suppliers', 'suppliers.id = po.supplier_id', 'left')
            ->where('po.id', $purchaseOrderId)
            ->get()
            ->getRowArray();

        if (! $purchaseOrder) {
            return null;
        }

        return [
            'purchase_order' => $purchaseOrder,
            'items' => $db->table('purchase_order_items poi')
                ->select('poi.*, products.product_name')
                ->select('supplier_orders.id as supplier_order_id, supplier_orders.po_no as supplier_order_po_no')
                ->select('supplier_order_items.qty_balance as current_po_qty_balance')
                ->join('products', 'products.id = poi.product_id', 'left')
                ->join('supplier_order_items', 'supplier_order_items.id = poi.supplier_order_item_id', 'left')
                ->join('supplier_orders', 'supplier_orders.id = supplier_order_items.supplier_order_id', 'left')
                ->where('poi.purchase_order_id', $purchaseOrderId)
                ->orderBy('poi.id', 'asc')
                ->get()
                ->getResultArray(),
            'allocations' => $db->table('payable_allocations pa')
                ->select('pa.purchase_order_id, pa.amount, p.id as payable_id, p.pr_no, p.date')
                ->join('payables p', 'p.id = pa.payable_id', 'left')
                ->where('p.status', 'posted')
                ->where('pa.purchase_order_id', $purchaseOrderId)
                ->orderBy('p.date', 'asc')
                ->orderBy('p.id', 'asc')
                ->get()
                ->getResultArray(),
            'delivery_allocations' => $this->purchaseOrderDeliveryAllocations($purchaseOrderId),
            'histories' => $this->purchaseOrderHistories($purchaseOrderId),
        ];
    }

    public function payable(int $payableId): ?array
    {
        $db = db_connect();
        $payable = $db->table('payables p')
            ->select('p.*, suppliers.name as supplier_name')
            ->join('suppliers', 'suppliers.id = p.supplier_id', 'left')
            ->where('p.id', $payableId)
            ->get()
            ->getRowArray();

        if (! $payable) {
            return null;
        }

        return [
            'payable' => $payable,
            'allocations' => $db->table('payable_allocations pa')
                ->select('pa.payable_id, pa.amount, po.id as purchase_order_id, po.po_no, po.date')
                ->join('purchase_orders po', 'po.id = pa.purchase_order_id', 'left')
                ->where('pa.payable_id', $payableId)
                ->orderBy('po.date', 'asc')
                ->orderBy('po.id', 'asc')
                ->get()
                ->getResultArray(),
            'other_accounts' => $db->table('payable_ledger pl')
                ->select('pl.payable_id, pl.account_title, pl.other_accounts, pl.entry_date, pl.pr_no')
                ->where('pl.payable_id', $payableId)
                ->where('pl.account_title IS NOT NULL', null, false)
                ->where('pl.other_accounts >', 0)
                ->orderBy('pl.entry_date', 'asc')
                ->orderBy('pl.id', 'asc')
                ->get()
                ->getResultArray(),
        ];
    }

    private function deliveryPickupAllocations(int $deliveryId): array
    {
        if (! $this->tableExists('delivery_pickup_allocations')) {
            return [];
        }

        return db_connect()->table('delivery_pickup_allocations dpa')
            ->select('dpa.delivery_id, dpa.purchase_order_id, dpa.product_id, dpa.qty_allocated')
            ->select('po.po_no as rr_no, po.date as rr_date')
            ->select('suppliers.name as supplier_name, products.product_name')
            ->join('purchase_orders po', 'po.id = dpa.purchase_order_id', 'left')
            ->join('suppliers', 'suppliers.id = po.supplier_id', 'left')
            ->join('products', 'products.id = dpa.product_id', 'left')
            ->where('dpa.delivery_id', $deliveryId)
            ->orderBy('po.date', 'asc')
            ->orderBy('po.id', 'asc')
            ->get()
            ->getResultArray();
    }

    private function purchaseOrderDeliveryAllocations(int $purchaseOrderId): array
    {
        if (! $this->tableExists('delivery_pickup_allocations')) {
            return [];
        }

        return db_connect()->table('delivery_pickup_allocations dpa')
            ->select('dpa.purchase_order_id, dpa.product_id, dpa.qty_allocated')
            ->select('d.id as delivery_id, d.dr_no, d.date, clients.name as client_name, products.product_name')
            ->join('deliveries d', 'd.id = dpa.delivery_id', 'left')
            ->join('clients', 'clients.id = d.client_id', 'left')
            ->join('products', 'products.id = dpa.product_id', 'left')
            ->where('dpa.purchase_order_id', $purchaseOrderId)
            ->where('d.voided_at', null)
            ->orderBy('d.date', 'asc')
            ->orderBy('d.id', 'asc')
            ->get()
            ->getResultArray();
    }

    private function deliveryHistories(int $deliveryId): array
    {
        if (! $this->tableExists('delivery_histories')) {
            return [];
        }

        return db_connect()->table('delivery_histories dh')
            ->select('dh.*, users.name as editor_name, users.username as editor_username')
            ->join('users', 'users.id = dh.edited_by', 'left')
            ->where('dh.delivery_id', $deliveryId)
            ->orderBy('dh.created_at', 'desc')
            ->orderBy('dh.id', 'desc')
            ->get()
            ->getResultArray();
    }

    private function purchaseOrderHistories(int $purchaseOrderId): array
    {
        if (! $this->tableExists('purchase_order_histories')) {
            return [];
        }

        return db_connect()->table('purchase_order_histories poh')
            ->select('poh.*, users.name as editor_name, users.username as editor_username')
            ->join('users', 'users.id = poh.edited_by', 'left')
            ->where('poh.purchase_order_id', $purchaseOrderId)
            ->orderBy('poh.created_at', 'desc')
            ->orderBy('poh.id', 'desc')
            ->get()
            ->getResultArray();
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
}
