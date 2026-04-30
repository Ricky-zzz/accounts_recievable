<?php
/**
 * @var string $title
 * @var string $action
 * @var int|string|null $clientId
 * @var array{id?: int|string, name?: string|null, payment_term?: int|string|null}|null $selectedClient
 * @var list<array{id: int|string, name: string, payment_term?: int|string|null}> $clients
 * @var string|false $clientsJson
 * @var int|string|null $defaultPaymentTerm
 * @var list<array{id: int|string, product_id?: string|null, product_name: string, unit_price?: int|float|string|null}> $products
 * @var string|false $productsJson
 * @var object|null $validation
 * @var list<string> $extraErrors
 * @var bool $embeddedForm
 */
?>
<?php if (empty($embeddedForm)): ?>
    <?= $this->extend('layout') ?>
    <?= $this->section('content') ?>
<?php endif; ?>
<?php $formClass = ! empty($embeddedForm) ? 'space-y-6' : 'mt-6 space-y-6'; ?>
<?php if (empty($embeddedForm)): ?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold">New Delivery</h1>
        <p class="mt-1 text-sm muted">Add a delivery receipt with one or more items.</p>
    </div>
    <a class="btn btn-secondary" href="<?= base_url(($selectedClient['id'] ?? $clientId ?? 0) ? 'clients/' . ($selectedClient['id'] ?? $clientId) . '/deliveries' : 'deliveries') ?>">Back</a>
</div>
<?php endif; ?>

<?php if (isset($validation)): ?>
    <div class="card mt-4 px-4 py-2 text-sm">
        <ul class="list-disc pl-5">
            <?php foreach ($validation->getErrors() as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (! empty($extraErrors ?? [])): ?>
    <div class="card mt-4 px-4 py-2 text-sm">
        <ul class="list-disc pl-5">
            <?php foreach ($extraErrors as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php
$selectedClientId = (string) (old('client_id') ?: ($selectedClient['id'] ?? ''));
$dateValue = (string) (old('date') ?: date('Y-m-d'));
$termValue = old('payment_term');
if (($termValue === null || $termValue === '') && isset($defaultPaymentTerm)) {
    $termValue = (string) $defaultPaymentTerm;
}
if ($termValue === null) {
    $termValue = '';
}
?>

<form class="<?= esc($formClass) ?>" method="post" action="<?= esc($action) ?>" x-data="deliveryForm()">
    <?= csrf_field() ?>
    <div class="grid gap-4 md:grid-cols-5">
        <?php if ($selectedClient): ?>
            <div>
                <label class="block text-sm font-medium">Client</label>
                <div class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    <?= esc((string) $selectedClient['name']) ?>
                </div>
                <input type="hidden" name="client_id" :value="selectedClientId">
            </div>
        <?php else: ?>
            <div>
                <label class="block text-sm font-medium" for="client_id">Client</label>
                <select class="input mt-1" id="client_id" name="client_id" x-model="selectedClientId" @change="onClientChange()" required>
                    <option value="">Select client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= esc((string) $client['id']) ?>" <?= old('client_id') == $client['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div>
            <label class="block text-sm font-medium" for="dr_no">DR Number</label>
            <input class="input mt-1" id="dr_no" name="dr_no" value="<?= esc(old('dr_no')) ?>" required>
        </div>
        <div>
            <label class="block text-sm font-medium" for="date">Date</label>
            <input class="input mt-1" id="date" name="date" type="date" value="<?= esc($dateValue) ?>" x-model="deliveryDate" @input="recomputeDueDate()" required>
        </div>
        <div>
            <label class="block text-sm font-medium" for="payment_term">Payment Term (days)</label>
            <input class="input mt-1" id="payment_term" name="payment_term" type="number" step="1" min="0" value="<?= esc((string) $termValue) ?>" x-model="paymentTerm" @input="recomputeDueDate()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="due_date_preview">Due Date</label>
            <input class="input mt-1" id="due_date_preview" type="date" x-model="dueDate" readonly>
        </div>
    </div>

    <div class="card p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">Items</h2>
            <button class="btn btn-secondary" type="button" @click="addItem()">Add Item</button>
        </div>

        <div class="mt-4 space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="grid gap-3 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium" :for="'product_' + index">Product</label>
                        <select class="input mt-1" :id="'product_' + index" x-model="item.product_id" @change="selectProduct(index)" :name="'items[' + index + '][product_id]'" required>
                            <option value="">Select product</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.product_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium" :for="'price_' + index">Unit Price</label>
                        <input class="input mt-1" :id="'price_' + index" type="number" step="0.01" x-model="item.unit_price" @input="updateLine(index)" :name="'items[' + index + '][unit_price]'" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium" :for="'qty_' + index">Qty</label>
                        <input class="input mt-1" :id="'qty_' + index" type="number" step="0.01" min="0" x-model="item.qty" @input="updateLine(index)" :name="'items[' + index + '][qty]'" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium" :for="'total_' + index">Total</label>
                        <input class="input mt-1" :id="'total_' + index" x-model="item.line_total" readonly>
                    </div>
                    <div class="flex items-end">
                        <button class="btn btn-secondary" type="button" @click="removeItem(index)" x-show="items.length > 1">Remove</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-gray-300 pt-4 text-lg font-bold">
            <span>Total</span>
            <span x-text="total()"></span>
        </div>
    </div>

    <div class="flex gap-3">
        <button class="btn btn-strong" type="submit">Save Delivery</button>
        <?php if (! empty($embeddedForm)): ?>
            <button class="btn btn-secondary" type="button" @click="$dispatch('close-delivery-form')">Cancel</button>
        <?php else: ?>
            <a class="btn btn-secondary" href="<?= base_url('deliveries') ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<script>
    function deliveryForm() {
        return {
            products: <?= $productsJson ?>,
            clients: <?= $clientsJson ?? '[]' ?>,
            selectedClientId: '<?= esc($selectedClientId, 'js') ?>',
            paymentTerm: '<?= esc((string) $termValue, 'js') ?>',
            deliveryDate: '<?= esc($dateValue, 'js') ?>',
            dueDate: '',
            items: [{
                product_id: '',
                qty: 1,
                unit_price: '',
                line_total: '0.00'
            }],
            init() {
                if (this.paymentTerm === '' && this.selectedClientId !== '') {
                    this.applyClientDefaultTerm();
                }
                this.recomputeDueDate();
            },
            onClientChange() {
                this.applyClientDefaultTerm();
                this.recomputeDueDate();
            },
            applyClientDefaultTerm() {
                const selected = this.clients.find((client) => String(client.id) === String(this.selectedClientId));
                this.paymentTerm = selected && selected.payment_term !== null ? String(selected.payment_term) : '';
            },
            recomputeDueDate() {
                if (!this.deliveryDate) {
                    this.dueDate = '';
                    return;
                }

                const term = parseInt(this.paymentTerm, 10);
                const days = Number.isFinite(term) && term >= 0 ? term : 0;
                const date = new Date(this.deliveryDate + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    this.dueDate = '';
                    return;
                }

                date.setDate(date.getDate() + days);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                this.dueDate = `${year}-${month}-${day}`;
            },
            addItem() {
                this.items.push({
                    product_id: '',
                    qty: 1,
                    unit_price: '',
                    line_total: '0.00'
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            selectProduct(index) {
                const item = this.items[index];
                const product = this.products.find((row) => String(row.id) === String(item.product_id));
                item.unit_price = product ? product.unit_price : '';
                this.updateLine(index);
            },
            updateLine(index) {
                const item = this.items[index];
                const qty = parseFloat(item.qty) || 0;
                const price = parseFloat(item.unit_price) || 0;
                item.line_total = (qty * price).toFixed(2);
            },
            total() {
                return this.items
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            }
        };
    }
</script>
<?php if (empty($embeddedForm)): ?>
    <?= $this->endSection() ?>
<?php endif; ?>
