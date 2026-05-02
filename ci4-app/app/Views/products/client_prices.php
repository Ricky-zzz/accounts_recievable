<?php
/**
 * @var array{id: int|string, product_id: string, product_name: string, unit_price: int|float|string} $product
 * @var list<array{id: int|string, name: string, assigned_price?: int|float|string|null}> $clients
 * @var \CodeIgniter\Pager\Pager|null $pager
 * @var string $search
 * @var int $rowOffset
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$search = (string) ($search ?? '');
$rowOffset = (int) ($rowOffset ?? 0);
$defaultPrice = (float) ($product['unit_price'] ?? 0);
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Assign Client Prices</h1>
        </div>
        <a class="btn btn-secondary" href="<?= base_url('products') ?>">Back</a>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="card p-4">
            <div class="text-sm muted">Product ID</div>
            <div class="mt-1 font-semibold"><?= esc((string) ($product['product_id'] ?? '')) ?></div>
            <div class="mt-3 text-sm muted">Product Name</div>
            <div class="mt-1 font-semibold"><?= esc((string) ($product['product_name'] ?? '')) ?></div>
        </div>
        <div class="card p-4">
            <div class="text-sm muted">Default Unit Price</div>
            <div class="mt-1 font-semibold"><?= esc(number_format($defaultPrice, 2)) ?></div>
        </div>
    </div>

    <form class="filter-card flex flex-wrap items-end gap-3 rounded border border-gray-200 p-4" method="get" action="<?= base_url('products/' . $product['id'] . '/client-prices') ?>" x-data>
        <div>
            <label class="block text-sm font-medium" for="q">Search Client</label>
            <input class="input mt-1" id="q" name="q" value="<?= esc($search) ?>" placeholder="Client name, email, or phone" @input.debounce.1000ms="$el.form.requestSubmit()">
        </div>
        <a class="btn btn-secondary" href="<?= base_url('products/' . $product['id'] . '/client-prices') ?>">Clear</a>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client Name</th>
                <th>Price</th>
                <th style="text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="4">No clients found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clients as $index => $client): ?>
                    <?php
                        $assignedPrice = $client['assigned_price'] ?? null;
                        $hasAssignedPrice = $assignedPrice !== null && $assignedPrice !== '';
                        $displayPrice = $hasAssignedPrice ? (float) $assignedPrice : $defaultPrice;
                    ?>
                    <tr>
                        <td><?= esc((string) ($rowOffset + $index + 1)) ?></td>
                        <td><?= esc((string) ($client['name'] ?? '')) ?></td>
                        <td class="<?= $hasAssignedPrice ? 'font-semibold' : '' ?>">
                            <?= esc(number_format($displayPrice, 2)) ?>
                        </td>
                        <td style="text-align: center;">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <form class="flex items-center gap-2" method="post" action="<?= base_url('products/' . $product['id'] . '/client-prices/' . $client['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input
                                        class="input w-32"
                                        name="price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="<?= $hasAssignedPrice ? esc((string) $assignedPrice) : '' ?>"
                                        placeholder="<?= esc(number_format($defaultPrice, 2)) ?>">
                                    <button class="btn btn-strong" type="submit">Save</button>
                                </form>
                                <?php if ($hasAssignedPrice): ?>
                                    <form method="post" action="<?= base_url('products/' . $product['id'] . '/client-prices/' . $client['id']) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="price" value="">
                                        <button class="btn btn-secondary" type="submit">Reset</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (isset($pager)): ?>
        <div class="flex justify-end">
            <?= $pager->links('client_prices') ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
