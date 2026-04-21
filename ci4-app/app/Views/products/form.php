<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h1 class="text-xl font-semibold"><?= esc($title) ?></h1>

<?php if (isset($validation)): ?>
    <div class="card mt-4 px-4 py-2 text-sm">
        <ul class="list-disc pl-5">
            <?php foreach ($validation->getErrors() as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="mt-6 max-w-xl space-y-4" method="post" action="<?= esc($action) ?>">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium" for="product_id">Product ID</label>
        <input class="input mt-1" id="product_id" name="product_id" value="<?= esc($product['product_id']) ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="product_name">Product Name</label>
        <input class="input mt-1" id="product_name" name="product_name" value="<?= esc($product['product_name']) ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="unit_price">Unit Price</label>
        <input class="input mt-1" id="unit_price" name="unit_price" type="number" step="0.01" value="<?= esc($product['unit_price']) ?>" required>
    </div>
    <div class="flex gap-3">
        <button class="btn" type="submit">Save</button>
        <a class="btn btn-secondary" href="<?= base_url('products') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>