<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h1 class="text-xl font-semibold">Dashboard</h1>
<p class="mt-1 text-sm muted">Choose a module to manage records.</p>
<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('clients') ?>">Clients</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('products') ?>">Products</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('banks') ?>">Banks</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('cashiers') ?>">Cashiers</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('deliveries') ?>">Deliveries</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('payments') ?>">Payments</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('other-accounts') ?>">Other Accounts</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('boa') ?>">BOA</a>
    <a class="card block px-4 py-6 text-center font-semibold" href="<?= base_url('excess') ?>">Excess</a>
</div>
<?= $this->endSection() ?>