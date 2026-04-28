<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$formErrors = session()->getFlashdata('form_errors');
if (! is_array($formErrors)) {
    $formErrors = [];
}

$formMode = (string) (session()->getFlashdata('form_mode') ?? '');
$formId = (int) (session()->getFlashdata('form_id') ?? 0);
$oldForm = [
    'product_id' => old('product_id'),
    'product_name' => old('product_name'),
    'unit_price' => old('unit_price'),
];
$search = (string) ($search ?? '');
$rowOffset = (int) ($rowOffset ?? 0);

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>

<div class="space-y-6" x-data="productManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Products</h1>
        <button class="btn" type="button" @click="openCreate()">New Product</button>
    </div>

    <form class="flex flex-wrap items-end gap-3" method="get" action="<?= base_url('products') ?>">
        <div>
            <label class="block text-sm font-medium" for="q">Search Product</label>
            <input class="input mt-1" id="q" name="q" value="<?= esc($search) ?>" placeholder="Product ID or name">
        </div>
        <button class="btn" type="submit">Filter</button>
        <a class="btn btn-secondary" href="<?= base_url('products') ?>">Clear</a>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product ID</th>
                <th>Name</th>
                <th>Unit Price</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5">No products found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $index => $product): ?>
                    <tr>
                        <td><?= esc((string) ($rowOffset + $index + 1)) ?></td>
                        <td><?= esc($product['product_id']) ?></td>
                        <td><?= esc($product['product_name']) ?></td>
                        <td><?= esc(number_format((float) $product['unit_price'], 2)) ?></td>
                        <td class="text-left">
                            <button class="btn-link" type="button" @click="openEdit(<?= (int) $product['id'] ?>)">Edit</button>
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

    <?php if (isset($pager)): ?>
        <div class="flex justify-end">
            <?= $pager->links('products') ?>
        </div>
    <?php endif; ?>

    <div class="modal-backdrop" x-show="open" x-cloak @click.self="closeModal()">
        <div class="modal-panel max-w-xl p-6">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Product' : 'New Product'"></h2>
                <button class="btn btn-secondary" type="button" @click="closeModal()">Close</button>
            </div>

            <form class="mt-4 space-y-4" method="post" :action="formAction">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium" for="product_id">Product ID</label>
                    <input class="input mt-1" id="product_id" name="product_id" x-model="form.product_id" required>
                    <span class="field-error" x-show="errors.product_id" x-text="errors.product_id"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="product_name">Product Name</label>
                    <input class="input mt-1" id="product_name" name="product_name" x-model="form.product_name" required>
                    <span class="field-error" x-show="errors.product_name" x-text="errors.product_name"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="unit_price">Unit Price</label>
                    <input class="input mt-1" id="unit_price" name="unit_price" type="number" step="0.01" x-model="form.unit_price" required>
                    <span class="field-error" x-show="errors.unit_price" x-text="errors.unit_price"></span>
                </div>
                <div class="flex gap-3">
                    <button class="btn" type="submit" x-text="isEdit ? 'Update Product' : 'Create Product'"></button>
                    <button class="btn btn-secondary" type="button" @click="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function productManager() {
        const products = <?= json_encode($products, $jsonFlags) ?>;
        const oldForm = <?= json_encode($oldForm, $jsonFlags) ?>;
        const formErrors = <?= json_encode($formErrors, $jsonFlags) ?>;
        const formMode = '<?= esc($formMode, 'js') ?>';
        const formId = <?= $formId ?>;
        const hasOldValues = Object.values(oldForm).some((value) => value !== null && String(value).trim() !== '');

        return {
            products,
            open: false,
            isEdit: false,
            formAction: '<?= base_url('products') ?>',
            errors: {},
            form: {
                product_id: '',
                product_name: '',
                unit_price: '',
            },
            init() {
                if (formMode === 'edit' && formId > 0) {
                    this.openEdit(formId);
                    if (hasOldValues) {
                        this.form = {
                            ...this.form,
                            ...oldForm,
                        };
                    }
                    this.errors = formErrors;
                    this.open = true;
                    return;
                }

                if (formMode === 'create') {
                    this.openCreate();
                    this.form = {
                        ...this.form,
                        ...oldForm,
                    };
                    this.errors = formErrors;
                    this.open = true;
                }
            },
            openCreate() {
                this.isEdit = false;
                this.formAction = '<?= base_url('products') ?>';
                this.errors = {};
                this.form = {
                    product_id: '',
                    product_name: '',
                    unit_price: '',
                };
                this.open = true;
            },
            openEdit(id) {
                const product = this.products.find((row) => Number(row.id) === Number(id));
                if (!product) {
                    return;
                }

                this.isEdit = true;
                this.formAction = `<?= base_url('products') ?>/${product.id}`;
                this.errors = {};
                this.form = {
                    product_id: product.product_id || '',
                    product_name: product.product_name || '',
                    unit_price: product.unit_price || '',
                };
                this.open = true;
            },
            closeModal() {
                this.open = false;
                this.errors = {};
            },
        };
    }
</script>
<?= $this->endSection() ?>
