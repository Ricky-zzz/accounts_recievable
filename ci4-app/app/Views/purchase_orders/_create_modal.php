<?php
/**
 * @var array<string, int|float|string|null>|null $selectedSupplier
 * @var list<array<string, int|float|string|null>> $suppliers
 * @var list<array<string, int|float|string|null>> $products
 */
?>
<div class="modal-backdrop" x-show="orderFormOpen" x-cloak @click.self="orderFormOpen = false">
    <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">New RR / Pickup</h2>
                <p class="mt-1 text-sm muted">Add a pickup receipt for a supplier.</p>
            </div>
            <button class="btn btn-secondary" type="button" @click="orderFormOpen = false">Close</button>
        </div>

        <form method="post" action="<?= base_url('purchase-orders') ?>" class="space-y-6">
            <?= csrf_field() ?>
            <div class="grid gap-4 md:grid-cols-5">
                <div>
                    <label class="block text-sm font-medium" for="supplier_id">Supplier</label>
                    <?php if ($selectedSupplier): ?>
                        <div class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm"><?= esc((string) $selectedSupplier['name']) ?></div>
                        <input type="hidden" name="supplier_id" x-model="newOrder.supplier_id">
                    <?php else: ?>
                        <select class="input mt-1" id="supplier_id" name="supplier_id" x-model="newOrder.supplier_id" @change="applySupplierTerm()" required>
                            <option value="">Select supplier</option>
                            <?php foreach ($suppliers as $row): ?>
                                <option value="<?= esc((string) $row['id']) ?>"><?= esc((string) $row['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="po_no_input">RR Number</label>
                    <input class="input mt-1" id="po_no_input" name="po_no" x-model="newOrder.po_no" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="date_input">Date</label>
                    <input class="input mt-1" id="date_input" name="date" type="date" x-model="newOrder.date" @input="recomputeDueDate(newOrder)" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="payment_term_input">Payment Term (days)</label>
                    <input class="input mt-1" id="payment_term_input" name="payment_term" type="number" step="1" min="0" x-model="newOrder.payment_term" @input="recomputeDueDate(newOrder)">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="due_date_preview">Due Date</label>
                    <input class="input mt-1" id="due_date_preview" type="date" x-model="newOrder.due_date" readonly>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Items</h3>
                    <button class="btn btn-secondary" type="button" @click="addNewItem()">Add Item</button>
                </div>
                <div class="mt-4 space-y-4">
                    <template x-for="(item, index) in newItems" :key="index">
                        <div class="grid gap-3 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium" :for="'product_' + index">Product</label>
                                <select class="input mt-1" :id="'product_' + index" x-model="item.product_id" @change="selectProduct(newItems, index)" :name="'items[' + index + '][product_id]'" required>
                                    <option value="">Select product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= esc((string) $product['id']) ?>"><?= esc((string) $product['product_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label class="block text-xs font-medium">Unit Price</label><input class="input mt-1" type="number" step="0.01" x-model="item.unit_price" @input="updateLine(item)" :name="'items[' + index + '][unit_price]'" required></div>
                            <div><label class="block text-xs font-medium">Qty</label><input class="input mt-1" type="number" step="0.00001" min="0" x-model="item.qty" @input="updateLine(item)" :name="'items[' + index + '][qty]'" required></div>
                            <div><label class="block text-xs font-medium">Total</label><input class="input mt-1" x-model="item.line_total" readonly></div>
                            <div class="flex items-end"><button class="btn btn-secondary" type="button" @click="newItems.splice(index, 1)" x-show="newItems.length > 1">Remove</button></div>
                        </div>
                    </template>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-gray-300 pt-4 text-lg font-bold">
                    <span>Total</span>
                    <span x-text="itemsTotal(newItems)"></span>
                </div>
            </div>
            <div class="flex gap-3">
                <button class="btn btn-strong" type="submit">Save RR / Pickup</button>
                <button class="btn btn-secondary" type="button" @click="orderFormOpen = false">Cancel</button>
            </div>
        </form>
    </div>
</div>
