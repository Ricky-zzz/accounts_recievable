<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php helper('permissions'); ?>
<div class="card overflow-hidden">
    <div class="border-b border-gray-200 px-6">
        <nav class="flex gap-2">
            <a class="tab-link tab-link-active" href="<?= base_url('/') ?>">Receivables</a>
            <a class="tab-link" href="<?= base_url('payables-dashboard') ?>">Payables</a>
        </nav>
    </div>
    <div class="flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <img class="h-16 w-16 rounded-2xl border border-stone-200 object-cover bg-white" src="<?= esc(base_url('logo.png')) ?>" alt="SRC Enterprises logo">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">SRC Enterprises Inc</p>
                <h1 class="mt-1 text-2xl font-semibold">Accounts Receivable Dashboard</h1>
                <p class="mt-2 text-sm muted">Choose a module to manage records.</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('clients') ?>">Clients</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('products') ?>">Products</a>
    <?php if (can_access('banks.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('banks') ?>">Banks</a>
    <?php endif; ?>
    <?php if (can_access('cashiers.view')): ?>
        <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('cashiers') ?>">Cashiers</a>
    <?php endif; ?>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('deliveries') ?>">Deliveries</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payments') ?>">Collections</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('reports/credits') ?>">Credits</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('reports/overdue') ?>">Overdue</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('reports/voided') ?>">Voided</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('boa') ?>">BOA</a>
</div>
<?= $this->endSection() ?>