<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Products</h1>
    <a class="btn" href="<?= base_url('products/new') ?>">New Product</a>
</div>

<table class="table mt-6">
    <thead>
        <tr>
            <th>Product ID</th>
            <th>Name</th>
            <th>Unit Price</th>
            <th class="text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($products)): ?>
            <tr>
                <td class="py-3" colspan="4">No products yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= esc($product['product_id']) ?></td>
                    <td><?= esc($product['product_name']) ?></td>
                    <td><?= esc(number_format((float) $product['unit_price'], 2)) ?></td>
                    <td class="text-left">
                        <a class="btn-link" href="<?= base_url('products/' . $product['id'] . '/edit') ?>">Edit</a>
                        <form class="inline" method="post" action="<?= base_url('products/' . $product['id'] . '/delete') ?>" onsubmit="return confirm('Delete this product?');">
                            <?= csrf_field() ?>
                            <button class="ml-3 btn-link" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>