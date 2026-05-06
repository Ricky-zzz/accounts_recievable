<?php

/**
 * @var array<string, mixed> $deliveryActionData
 */
$deliveryActionData = $deliveryActionData ?? [];
$products = $deliveryActionData['products'] ?? [];
?>

<div class="modal-backdrop" x-show="editOpen" x-cloak @click.self="closeEdit()">
    <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Edit Delivery</h2>
                <p class="mt-1 text-sm muted" x-text="selectedActionDelivery() ? 'DR# ' + selectedActionDelivery().dr_no : ''"></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeEdit()">Close</button>
        </div>

        <form method="post" :action="selectedActionDelivery() ? '<?= base_url('deliveries') ?>/' + selectedActionDelivery().id : '#'" class="space-y-6">
            <?= csrf_field() ?>
            <div class="grid gap-4 md:grid-cols-5">
                <div>
                    <label class="block text-sm font-medium">Client</label>
                    <div class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm" x-text="selectedActionDelivery() ? selectedActionDelivery().client_name : ''"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="edit_dr_no">DR Number</label>
                    <input class="input mt-1" id="edit_dr_no" name="dr_no" x-model="editDelivery.dr_no" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="edit_date">Date</label>
                    <input class="input mt-1" id="edit_date" name="date" type="date" x-model="editDelivery.date" @input="recomputeEditDueDate()" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="edit_payment_term">Payment Term (days)</label>
                    <input class="input mt-1" id="edit_payment_term" name="payment_term" type="number" step="1" min="0" x-model="editDelivery.payment_term" @input="recomputeEditDueDate()">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="edit_due_date_preview">Due Date</label>
                    <input class="input mt-1" id="edit_due_date_preview" type="date" x-model="editDelivery.due_date" readonly>
                </div>
            </div>

            <input type="hidden" name="pickup_id" :value="editPickup.id">
            <input type="hidden" name="pickup_product_id" :value="editPickup.product_id">

            <div class="card p-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-64 flex-1">
                        <label class="block text-sm font-medium" for="edit_pickup_search">Connected RR</label>
                        <input class="input mt-1" id="edit_pickup_search" x-model="editPickupQuery" @input.debounce.600ms="handleEditPickupQueryInput()" @keydown.enter.prevent="searchEditPickups()" placeholder="Search RR number, supplier, or product">
                    </div>
                    <button class="btn btn-secondary" type="button" @click="clearEditPickup()">Clear</button>
                </div>

                <div class="mt-3 text-sm muted" x-show="editPickupSearching || editPickupMessage" x-text="editPickupSearching ? 'Searching RRs...' : editPickupMessage"></div>

                <div class="mt-3 overflow-x-auto rounded border border-gray-200" x-show="editPickupResults.length > 0" x-cloak>
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
                            <template x-for="row in editPickupResults" :key="row.purchase_order_id + '-' + row.product_id">
                                <tr class="hover:bg-gray-50" tabindex="0" @keydown.enter.prevent="selectEditPickup(row)">
                                    <td class="font-semibold" x-text="row.rr_no"></td>
                                    <td x-text="row.supplier_name"></td>
                                    <td x-text="row.product_name"></td>
                                    <td class="tabular-nums" x-text="formatQty(row.qty)"></td>
                                    <td class="tabular-nums" x-text="formatQty(row.remaining_qty)"></td>
                                    <td class="text-right">
                                        <button class="btn btn-secondary" type="button" @click="selectEditPickup(row)">Choose</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 overflow-x-auto rounded border border-gray-200" x-show="editPickup.id" x-cloak>
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
                                <td x-text="editPickup.supplier_name"></td>
                                <td x-text="editPickup.product_name"></td>
                                <td class="tabular-nums" x-text="formatQty(editPickupAvailableQty())"></td>
                                <td class="tabular-nums" x-text="formatQty(editPickupBalanceAfterDelivery())"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Items</h3>
                    <button class="btn btn-secondary" type="button" @click="addEditItem()">Add Item</button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-for="(item, index) in editItems" :key="index">
                        <div class="grid gap-3 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium" :for="'edit_product_' + index">Product</label>
                                <select class="input mt-1" :id="'edit_product_' + index" x-model="item.product_id" @change="selectEditProduct(index)" :name="'items[' + index + '][product_id]'" required>
                                    <option value="">Select product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= esc((string) $product['id']) ?>"><?= esc((string) $product['product_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium" :for="'edit_price_' + index">Unit Price</label>
                                <input class="input mt-1" :id="'edit_price_' + index" type="number" step="0.01" x-model="item.unit_price" @input="updateEditLine(index)" :name="'items[' + index + '][unit_price]'" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium" :for="'edit_qty_' + index">Qty</label>
                                <input class="input mt-1" :id="'edit_qty_' + index" type="number" step="0.00001" min="0" x-model="item.qty" @input="updateEditLine(index)" :name="'items[' + index + '][qty]'" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium" :for="'edit_total_' + index">Total</label>
                                <input class="input mt-1" :id="'edit_total_' + index" x-model="item.line_total" readonly>
                            </div>
                            <div class="flex items-end">
                                <button class="btn btn-secondary" type="button" @click="removeEditItem(index)" x-show="editItems.length > 1">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-gray-300 pt-4 text-sm">
                    <span class="font-semibold">Total</span>
                    <span x-text="editTotal()"></span>
                </div>
            </div>

            <div class="flex gap-3">
                <button class="btn btn-strong" type="submit">Save Changes</button>
                <button class="btn btn-secondary" type="button" @click="closeEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" x-show="voidOpen" x-cloak @click.self="closeVoid()">
    <div class="modal-panel max-w-xl p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Void Delivery</h2>
                <p class="mt-1 text-sm muted">Voiding cannot be undone.</p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeVoid()">Close</button>
        </div>

        <form method="post" :action="selectedActionDelivery() ? '<?= base_url('deliveries') ?>/' + selectedActionDelivery().id + '/void' : '#'" class="space-y-4">
            <?= csrf_field() ?>
            <div class="card p-4 text-sm">
                <div class="flex justify-between gap-4">
                    <span>DR#</span>
                    <span class="font-medium" x-text="selectedActionDelivery() ? selectedActionDelivery().dr_no : ''"></span>
                </div>
                <div class="mt-2 flex justify-between gap-4">
                    <span>Date</span>
                    <span x-text="selectedActionDelivery() ? selectedActionDelivery().date : ''"></span>
                </div>
                <div class="mt-2 flex justify-between gap-4">
                    <span>Total</span>
                    <span x-text="formatAmount(selectedActionDelivery() ? selectedActionDelivery().total_amount : 0)"></span>
                </div>
                <div class="mt-2 flex justify-between gap-4">
                    <span>Current Balance</span>
                    <span x-text="formatAmount(selectedActionDelivery() ? selectedActionDelivery().balance : 0)"></span>
                </div>
            </div>

            <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                This will mark the delivery as voided and insert a reversing ledger row with account title Voided.
            </div>

            <div>
                <label class="block text-sm font-medium" for="void_reason">Reason</label>
                <textarea class="input mt-1" id="void_reason" name="void_reason" rows="3" required></textarea>
            </div>

            <div class="flex gap-3">
                <button class="btn" type="submit">Void Delivery</button>
                <button class="btn btn-secondary" type="button" @click="closeVoid()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" x-show="historyOpen" x-cloak @click.self="closeHistory()">
    <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
        <div class="sticky top-0 z-10 -mx-6 -mt-6 border-b border-gray-200 bg-white p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Delivery History</h2>
                    <p class="mt-1 text-sm muted" x-text="selectedActionDelivery() ? 'DR# ' + selectedActionDelivery().dr_no + ' | Current total ' + formatAmount(selectedActionDelivery().total_amount) : ''"></p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeHistory()">Close</button>
            </div>
        </div>

        <div class="mt-5 space-y-4">
            <template x-if="selectedHistories().length === 0">
                <div class="card p-4 text-sm">No history recorded for this delivery yet.</div>
            </template>
            <template x-for="history in selectedHistories()" :key="history.id">
                <div class="card p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold" x-text="history.action === 'void' ? 'Voided' : 'Edited'"></h3>
                            <p class="mt-1 text-sm muted" x-text="history.change_summary || 'No summary available.'"></p>
                        </div>
                        <div class="text-right text-xs muted">
                            <p x-text="history.created_at"></p>
                            <p x-text="history.editor_name || history.editor_username || 'System'"></p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                        <div class="rounded border border-gray-200 p-3">
                            <p class="font-semibold">Before</p>
                            <p class="mt-2">DR#: <span x-text="historyDelivery(history.old_delivery_json).dr_no || '-'"></span></p>
                            <p>Date: <span x-text="historyDelivery(history.old_delivery_json).date || '-'"></span></p>
                            <p>Total: <span x-text="formatAmount(historyDelivery(history.old_delivery_json).total_amount || 0)"></span></p>
                            <div class="mt-3">
                                <p class="font-semibold">Items</p>
                                <table class="table mt-2 text-xs">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="historyItems(history.old_items_json).length === 0">
                                            <tr>
                                                <td class="py-2" colspan="4">No items recorded.</td>
                                            </tr>
                                        </template>
                                        <template x-for="(item, index) in historyItems(history.old_items_json)" :key="index">
                                            <tr>
                                                <td x-text="item.product_name || item.product_id || '-'" class="truncate"></td>
                                                <td x-text="formatQty(item.qty)"></td>
                                                <td x-text="formatAmount(item.unit_price || 0)"></td>
                                                <td x-text="formatAmount(item.line_total || 0)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="rounded border border-gray-200 p-3">
                            <p class="font-semibold">After</p>
                            <p class="mt-2">DR#: <span x-text="historyDelivery(history.new_delivery_json).dr_no || '-'"></span></p>
                            <p>Date: <span x-text="historyDelivery(history.new_delivery_json).date || '-'"></span></p>
                            <p>Total: <span x-text="formatAmount(historyDelivery(history.new_delivery_json).total_amount || 0)"></span></p>
                            <div class="mt-3">
                                <p class="font-semibold">Items</p>
                                <table class="table mt-2 text-xs">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="historyItems(history.new_items_json).length === 0">
                                            <tr>
                                                <td class="py-2" colspan="4">No items recorded.</td>
                                            </tr>
                                        </template>
                                        <template x-for="(item, index) in historyItems(history.new_items_json)" :key="index">
                                            <tr>
                                                <td x-text="item.product_name || item.product_id || '-'" class="truncate"></td>
                                                <td x-text="formatQty(item.qty)"></td>
                                                <td x-text="formatAmount(item.unit_price || 0)"></td>
                                                <td x-text="formatAmount(item.line_total || 0)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>