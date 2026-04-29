<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php helper('permissions'); ?>
<div class="card overflow-hidden">
    <div class="border-b border-gray-200 px-6">
        <nav class="flex gap-2">
            <a class="tab-link" href="<?= base_url('/') ?>">Receivables</a>
            <a class="tab-link tab-link-active" href="<?= base_url('payables-dashboard') ?>">Payables</a>
        </nav>
    </div>
    <div class="flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <img class="h-16 w-16 rounded-2xl border border-stone-200 object-cover bg-white" src="<?= esc(base_url('logo.png')) ?>" alt="SRC Enterprises logo">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">SRC Enterprises Inc</p>
                <h1 class="mt-1 text-2xl font-semibold">Accounts Payable Dashboard</h1>
                <p class="mt-2 text-sm muted">Choose a payables module to manage records.</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <?php if (can_access('suppliers.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('suppliers') ?>">Suppliers</a>
    <?php endif; ?>
    <?php if (can_access('products.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payables/products') ?>">Products</a>
    <?php endif; ?>
    <?php if (can_access('banks.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payables/banks') ?>">Banks</a>
    <?php endif; ?>
    <?php if (can_access('purchase_orders.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('purchase-orders') ?>">Purchase Orders</a>
    <?php endif; ?>
    <?php if (can_access('payables.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payables') ?>">Payments</a>
    <?php endif; ?>
    <?php if (can_access('payable_reports.credits.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payable-reports/credits') ?>">Credits</a>
    <?php endif; ?>
    <?php if (can_access('payable_reports.overdue.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payable-reports/overdue') ?>">Overdue</a>
    <?php endif; ?>
    <?php if (can_access('payable_reports.voided.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payable-reports/voided') ?>">Voided</a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
