<?php
/**
 * @var array<string, int|float|string|null> $supplierOrder
 * @var list<array<string, int|float|string|null>> $pickupItems
 * @var int $supplierId
 * @var int $supplierOrderId
 */
?>
<div class="modal-backdrop" x-show="pickupFormOpen" x-cloak @click.self="closePickupForm()">
    <div class="modal-panel max-h-[92vh] max-w-4xl overflow-y-auto p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">New RR / Pickup</h2>
                <p class="mt-2 text-base font-semibold">PO <?= esc((string) ($supplierOrder['po_no'] ?? '')) ?> | <?= esc((string) ($supplierOrder['supplier_name'] ?? '')) ?></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closePickupForm()">Close</button>
        </div>

        <?php if (empty($pickupItems)): ?>
            <div class="card p-4 text-sm">No open PO balance found for this ledger.</div>
        <?php else: ?>
            <form method="post" action="<?= base_url('purchase-orders') ?>" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="supplier_id" value="<?= esc((string) $supplierId) ?>">
                <input type="hidden" name="return_to_supplier_order_ledger" value="<?= esc((string) $supplierOrderId) ?>">

                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium" for="po_ledger_rr_no">RR Number</label>
                        <input class="input mt-1" id="po_ledger_rr_no" name="po_no" x-model="pickupForm.po_no" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="po_ledger_date">Date</label>
                        <input class="input mt-1" id="po_ledger_date" name="date" type="date" x-model="pickupForm.date" @input="recomputePickupDueDate()" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="po_ledger_payment_term">Payment Term (days)</label>
                        <input class="input mt-1" id="po_ledger_payment_term" name="payment_term" type="number" step="1" min="0" x-model="pickupForm.payment_term" @input="recomputePickupDueDate()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="po_ledger_due_date">Due Date</label>
                        <input class="input mt-1" id="po_ledger_due_date" type="date" x-model="pickupForm.due_date" readonly>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="po_ledger_item">Product</label>
                        <?php if (count($pickupItems) === 1): ?>
                            <div class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm" x-text="selectedPickupItem() ? selectedPickupItem().product_name : '-'"></div>
                        <?php else: ?>
                            <select class="input mt-1" id="po_ledger_item" x-model="pickupForm.supplier_order_item_id" @change="syncPickupItem()">
                                <?php foreach ($pickupItems as $item): ?>
                                    <option value="<?= esc((string) ($item['id'] ?? '')) ?>"><?= esc((string) ($item['product_name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium">PO Balance</p>
                        <p class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-semibold" x-text="formatQty(selectedPickupItem() ? selectedPickupItem().qty_balance : 0)"></p>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium" for="po_ledger_unit_price">Unit Price</label>
                            <input class="input mt-1" id="po_ledger_unit_price" name="items[0][unit_price]" type="number" step="0.01" min="0" x-model="pickupForm.unit_price" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="po_ledger_qty">Qty</label>
                            <input class="input mt-1" id="po_ledger_qty" name="items[0][qty]" type="number" step="0.00001" min="0" x-model="pickupForm.qty" required>
                        </div>
                        <div>
                            <p class="text-sm muted">Line Total</p>
                            <p class="mt-3 font-semibold" x-text="formatAmount(pickupLineTotal())"></p>
                        </div>
                    </div>
                    <input type="hidden" name="items[0][supplier_order_item_id]" :value="pickupForm.supplier_order_item_id">
                    <input type="hidden" name="items[0][product_id]" :value="selectedPickupItem() ? selectedPickupItem().product_id : ''">
                </div>

                <div class="flex gap-3">
                    <button class="btn btn-strong" type="submit" :disabled="!selectedPickupItem() || Number(pickupForm.qty || 0) <= 0 || Number(pickupForm.qty || 0) > Number(selectedPickupItem().qty_balance || 0)">Save RR / Pickup</button>
                    <button class="btn btn-secondary" type="button" @click="closePickupForm()">Cancel</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
