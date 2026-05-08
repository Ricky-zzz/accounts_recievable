<?php
/**
 * @var list<array<string, int|float|string|null>> $products
 */
?>
<div class="modal-backdrop" x-show="editOpen" x-cloak @click.self="closeEdit()">
    <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Edit RR / Pickup</h2>
                <p class="mt-1 text-sm muted" x-text="selectedActionOrder() ? 'RR# ' + selectedActionOrder().po_no : ''"></p>
            </div><button class="btn btn-secondary" type="button" @click="closeEdit()">Close</button>
        </div>
        <form method="post" :action="selectedActionOrder() ? '<?= base_url('purchase-orders') ?>/' + selectedActionOrder().id : '#'" class="space-y-6">
            <?= csrf_field() ?>
            <div class="grid gap-4 md:grid-cols-5">
                <div><label class="block text-sm font-medium">Supplier</label>
                    <div class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm" x-text="selectedActionOrder() ? selectedActionOrder().supplier_name : ''"></div>
                </div>
                <div><label class="block text-sm font-medium">RR Number</label><input class="input mt-1" name="po_no" x-model="editOrder.po_no" required></div>
                <div><label class="block text-sm font-medium">Date</label><input class="input mt-1" name="date" type="date" x-model="editOrder.date" @input="recomputeDueDate(editOrder)" required></div>
                <div><label class="block text-sm font-medium">Payment Term (days)</label><input class="input mt-1" name="payment_term" type="number" step="1" min="0" x-model="editOrder.payment_term" @input="recomputeDueDate(editOrder)"></div>
                <div><label class="block text-sm font-medium">Due Date</label><input class="input mt-1" type="date" x-model="editOrder.due_date" readonly></div>
            </div>
            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Items</h3><button class="btn btn-secondary" type="button" @click="addEditItem()">Add Item</button>
                </div>
                <div class="mt-4 space-y-4">
                    <template x-for="(item, index) in editItems" :key="index">
                        <div class="grid gap-3 sm:grid-cols-6">
                            <div class="sm:col-span-2"><label class="block text-xs font-medium">Product</label><select class="input mt-1" x-model="item.product_id" @change="selectProduct(editItems, index)" :name="'items[' + index + '][product_id]'" required>
                                    <option value="">Select product</option><?php foreach ($products as $product): ?><option value="<?= esc((string) $product['id']) ?>"><?= esc((string) $product['product_name']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div><label class="block text-xs font-medium">Unit Price</label><input class="input mt-1" type="number" step="0.01" x-model="item.unit_price" @input="updateLine(item)" :name="'items[' + index + '][unit_price]'" required></div>
                            <div><label class="block text-xs font-medium">Qty</label><input class="input mt-1" type="number" step="0.00001" min="0" x-model="item.qty" @input="updateLine(item)" :name="'items[' + index + '][qty]'" required></div>
                            <div><label class="block text-xs font-medium">Total</label><input class="input mt-1" x-model="item.line_total" readonly></div>
                            <div class="flex items-end"><button class="btn btn-secondary" type="button" @click="editItems.splice(index, 1)" x-show="editItems.length > 1">Remove</button></div>
                        </div>
                    </template>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-gray-300 pt-4 text-sm"><span class="font-semibold">Total</span><span x-text="itemsTotal(editItems)"></span></div>
            </div>
            <div class="flex gap-3"><button class="btn btn-strong" type="submit">Save Changes</button><button class="btn btn-secondary" type="button" @click="closeEdit()">Cancel</button></div>
        </form>
    </div>
</div>
