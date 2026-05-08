<?php
/**
 * @var array{id: int|string, name: string, payment_term?: int|string|null} $supplier
 * @var array{id?: int|string, name?: string|null, username?: string|null}|null $assignedUser
 * @var int|null $activeReceipt
 * @var int|null $rangeEnd
 * @var list<array{id: int|string, bank_name: string}> $banks
 * @var list<array{purchase_order_id: int|string, po_no?: string|null, date?: string|null, due_date?: string|null, total_amount?: int|float|string|null, allocated_amount?: int|float|string|null, balance?: int|float|string|null}> $unpaidPurchaseOrders
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?= view('payables/_form_partial', [
    'supplier' => $supplier,
    'assignedUser' => $assignedUser,
    'activeReceipt' => $activeReceipt,
    'rangeEnd' => $rangeEnd,
    'banks' => $banks,
    'unpaidPurchaseOrders' => $unpaidPurchaseOrders,
]) ?>
<?= $this->endSection() ?>
