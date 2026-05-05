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
 * @var string|false $clientPriceMapJson
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

<form class="<?= esc($formClass) ?>" method="post" action="<?= esc($action) ?>" x-data="deliveryForm()" @submit="syncPickupFields()">
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

    <input type="hidden" name="pickup_id" x-model="pickup.id" x-ref="pickupId">
    <input type="hidden" name="pickup_product_id" x-model="pickup.product_id" x-ref="pickupProductId">

    <div class="card p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <label class="block text-sm font-medium" for="pickup_search">Connected RR</label>
                <input class="input mt-1" id="pickup_search" x-model="pickupQuery" @input.debounce.600ms="handlePickupQueryInput()" @keydown.enter.prevent="searchPickups()" placeholder="Search RR number, supplier, or product">
            </div>
            <button class="btn btn-secondary" type="button" @click="clearPickup()">Clear</button>
        </div>

        <div class="mt-3 text-sm muted" x-show="pickupSearching || pickupMessage" x-text="pickupSearching ? 'Searching RRs...' : pickupMessage"></div>

        <div class="mt-3 overflow-x-auto rounded border border-gray-200" x-show="pickupResults.length > 0" x-cloak>
            <table class="table">
                <thead>
                    <tr>
                        <th>RR#</th>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Remaining</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pickupResults" :key="row.purchase_order_id + '-' + row.product_id">
                        <tr class="hover:bg-gray-50" tabindex="0" @keydown.enter.prevent="selectPickup(row)">
                            <td class="font-semibold" x-text="row.rr_no"></td>
                            <td x-text="row.supplier_name"></td>
                            <td x-text="row.product_name"></td>
                            <td class="tabular-nums" x-text="formatAmount(row.qty)"></td>
                            <td class="tabular-nums" x-text="formatAmount(row.remaining_qty)"></td>
                            <td class="text-right">
                                <button class="btn btn-secondary" type="button" @click="selectPickup(row)">Choose</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-3 overflow-x-auto rounded border border-gray-200" x-show="pickup.id" x-cloak>
            <table class="table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Deliverable / Loss</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td x-text="pickup.supplier_name"></td>
                        <td x-text="pickup.product_name"></td>
                        <td class="tabular-nums" x-text="formatAmount(pickup.remaining_qty)"></td>
                        <td class="tabular-nums" x-text="formatAmount(pickupBalanceAfterDelivery())"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">Items</h2>
            <button class="btn btn-secondary" type="button" @click="addItem()">Add Item</button>
        </div>

        <div class="mt-4 space-y-4">
            <template x-for="(item, index) in items" :key="item.product_id + '-' + index">
                <div class="grid gap-3 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium" :for="'product_' + index">Product</label>
                        <select class="input mt-1" :id="'product_' + index" x-model="item.product_id" @change="selectProduct(index)" :name="'items[' + index + '][product_id]'" required>
                            <option value="">Select product</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="String(product.id)" x-text="product.product_name"></option>
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
            clientPriceMap: <?= $clientPriceMapJson ?? '[]' ?>,
            clients: <?= $clientsJson ?? '[]' ?>,
            pickupSearchUrl: '<?= base_url('deliveries/pickups/search') ?>',
            selectedClientId: '<?= esc($selectedClientId, 'js') ?>',
            paymentTerm: '<?= esc((string) $termValue, 'js') ?>',
            deliveryDate: '<?= esc($dateValue, 'js') ?>',
            dueDate: '',
            pickupQuery: '',
            pickupSearching: false,
            pickupSearchToken: 0,
            pickupMessage: '',
            pickupResults: [],
            pickup: {
                id: '',
                product_id: '',
                rr_no: '',
                supplier_name: '',
                product_name: '',
                remaining_qty: 0,
            },
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
                this.refreshItemPrices();
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
            handlePickupQueryInput() {
                if (this.pickup.id && String(this.pickupQuery || '').trim() !== String(this.pickup.rr_no || '').trim()) {
                    this.clearPickup(false);
                }
                if (String(this.pickupQuery || '').trim() === '') {
                    this.pickupResults = [];
                    this.pickupMessage = '';
                    return;
                }
                this.searchPickups();
            },
            async searchPickups() {
                const query = String(this.pickupQuery || '').trim();
                const queryKey = query.toLowerCase();
                if (query === '') {
                    this.pickupResults = [];
                    this.pickupMessage = '';
                    return;
                }
                const token = ++this.pickupSearchToken;
                this.pickupSearching = true;
                this.pickupMessage = '';
                try {
                    const response = await fetch(this.pickupSearchUrl + '?q=' + encodeURIComponent(query), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (token !== this.pickupSearchToken) {
                        return;
                    }
                    this.pickupResults = Array.isArray(data.results) ? data.results : [];
                    const exact = this.pickupResults.find((row) => {
                        return String(row.rr_no || '').trim().toLowerCase() === queryKey;
                    });
                    if (exact) {
                        this.selectPickup(exact);
                        return;
                    }
                    if (this.pickupResults.length === 0) {
                        this.pickupMessage = 'No open RR found.';
                    }
                } catch (error) {
                    if (token !== this.pickupSearchToken) {
                        return;
                    }
                    this.pickupResults = [];
                    this.pickupMessage = 'Unable to search RRs right now.';
                } finally {
                    if (token === this.pickupSearchToken) {
                        this.pickupSearching = false;
                    }
                }
            },
            selectPickup(row) {
                const pickupId = row.purchase_order_id || row.id || '';
                const productId = this.resolveProductId(row.product_id || row.productId || '');
                this.pickup = {
                    id: pickupId,
                    product_id: String(productId),
                    rr_no: row.rr_no || '',
                    supplier_name: row.supplier_name || '',
                    product_name: row.product_name || '',
                    remaining_qty: row.remaining_qty || 0,
                };
                this.pickupQuery = row.rr_no || '';
                this.pickupResults = [];
                this.pickupMessage = '';
                this.items = [{
                    product_id: String(productId),
                    qty: row.remaining_qty || 0,
                    unit_price: this.effectiveUnitPrice(String(productId), this.selectedClientId),
                    line_total: '0.00'
                }];
                this.updateLine(0);
                if (this.$refs.pickupId) {
                    this.$refs.pickupId.value = pickupId;
                }
                if (this.$refs.pickupProductId) {
                    this.$refs.pickupProductId.value = String(productId);
                }
            },
            clearPickup(resetQuery = true) {
                this.pickupSearchToken++;
                this.pickup = {
                    id: '',
                    product_id: '',
                    rr_no: '',
                    supplier_name: '',
                    product_name: '',
                    remaining_qty: 0
                };
                this.pickupSearching = false;
                this.pickupResults = [];
                this.pickupMessage = '';
                if (this.$refs.pickupId) {
                    this.$refs.pickupId.value = '';
                }
                if (this.$refs.pickupProductId) {
                    this.$refs.pickupProductId.value = '';
                }
                if (resetQuery) {
                    this.pickupQuery = '';
                }
            },
            pickupDeliveryQty() {
                if (!this.pickup.id) {
                    return 0;
                }

                return this.items
                    .filter((item) => String(item.product_id) === String(this.pickup.product_id))
                    .reduce((sum, item) => sum + (parseFloat(item.qty) || 0), 0);
            },
            pickupBalanceAfterDelivery() {
                return (parseFloat(this.pickup.remaining_qty) || 0) - this.pickupDeliveryQty();
            },
            selectProduct(index) {
                const item = this.items[index];
                if (this.pickup.id && String(item.product_id) !== String(this.pickup.product_id)) {
                    this.clearPickup(false);
                }
                item.unit_price = this.effectiveUnitPrice(item.product_id, this.selectedClientId);
                this.updateLine(index);
            },
            refreshItemPrices() {
                this.items.forEach((item, index) => {
                    if (item.product_id) {
                        this.selectProduct(index);
                    }
                });
            },
            resolveProductId(value) {
                const rawValue = String(value || '');
                if (rawValue === '') {
                    return '';
                }

                const match = this.products.find((product) => {
                    return String(product.id) === rawValue || String(product.product_id) === rawValue;
                });

                return match ? String(match.id) : rawValue;
            },
            effectiveUnitPrice(productId, clientId) {
                const product = this.products.find((row) => String(row.id) === String(productId));
                const defaultPrice = product ? product.unit_price : '';
                const clientPrices = this.clientPriceMap[String(clientId)] || {};
                const specialPrice = clientPrices[String(productId)];

                return specialPrice !== undefined && specialPrice !== null && String(specialPrice) !== '' ?
                    specialPrice :
                    defaultPrice;
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
            },
            syncPickupFields() {
                if (this.$refs.pickupId) {
                    this.$refs.pickupId.value = this.pickup.id || '';
                }
                if (this.$refs.pickupProductId) {
                    this.$refs.pickupProductId.value = this.pickup.product_id || '';
                }
            },
            formatAmount(value) {
                return (Math.round((parseFloat(value) || 0) * 100) / 100).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }
        };
    }
</script>
<?php if (empty($embeddedForm)): ?>
    <?= $this->endSection() ?>
<?php endif; ?>