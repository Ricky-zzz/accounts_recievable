<?php

/**
 * @var string $fromDate
 * @var string $toDate
 * @var string $poNo
 * @var list<array<string, int|float|string|null>> $purchaseOrders
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $historiesByPurchaseOrder
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 * @var string $pagerLinks
 * @var int $rowOffset
 * @var array<string, mixed> $formData
 * @var array<string, mixed> $quickPayData
 * @var array<string, mixed> $actionData
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$ordersJson = json_encode($purchaseOrders ?? [], $jsonFlags);
$itemsJson = json_encode($itemsByPurchaseOrder ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByPurchaseOrder ?? [], $jsonFlags);
$historiesJson = json_encode($historiesByPurchaseOrder ?? [], $jsonFlags);
$products = $formData['products'] ?? [];
$suppliers = $formData['suppliers'] ?? [];
$selectedSupplier = $formData['selectedSupplier'] ?? null;
$defaultPaymentTerm = $formData['defaultPaymentTerm'] ?? '';
$productsJson = json_encode($products, $jsonFlags);
$suppliersJson = json_encode($suppliers, $jsonFlags);
$listUrl = base_url('purchase-orders');
$printParams = ['from_date' => $fromDate ?? '', 'to_date' => $toDate ?? '', 'po_no' => $poNo ?? ''];
?>

<div x-data="purchaseOrderList()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">
                RR / Pickups
            </h1>
            <p class="mt-1 text-sm muted">Filter pickups by date range or RR number.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button class="btn btn-strong" type="button" @click="openOrderForm()">New Pickup</button>
            <a class="btn btn-secondary" href="<?= base_url('supplier-orders') ?>">PO</a>
            <a class="btn btn-secondary" href="<?= base_url('payables') ?>">Payments</a>
            <a class="btn btn-secondary" target="_blank" href="<?= base_url('purchase-orders/print') ?>?<?= esc(http_build_query($printParams)) ?>">Print</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= esc($listUrl) ?>" class="filter-card mt-4 rounded border border-gray-200 p-4" x-data>
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium" for="po_no">RR Number</label>
                <input class="input mt-1" id="po_no" name="po_no" value="<?= esc($poNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="from_date">From Date</label>
                <input class="input mt-1" id="from_date" name="from_date" type="date" value="<?= esc($fromDate ?? '') ?>" @change="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="to_date">To Date</label>
                <input class="input mt-1" id="to_date" name="to_date" type="date" value="<?= esc($toDate ?? '') ?>" @change="$el.form.requestSubmit()">
            </div>
            <div class="flex items-end gap-2">
                <a class="btn btn-secondary" href="<?= esc($listUrl) ?>">Clear</a>
            </div>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>RR #</th>
                <th>Due Date</th>
                <th>Term</th>
                <th class="text-right" style="text-align: right;">Total Amount</th>
                <th class="text-right" style="text-align: right;">Paid</th>
                <th class="text-right" style="text-align: right;">Balance</th>
                <th class="text-center" style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchaseOrders)): ?>
                <tr>
                    <td class="py-3" colspan="10">No pickups found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchaseOrders as $index => $order): ?>
                    <tr>
                        <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                        <td><?= esc((string) $order['date']) ?></td>
                        <td><?= esc((string) ($order['supplier_name'] ?? '')) ?></td>
                        <td>
                            <button class="btn-link" type="button" @click="openPoDetails(<?= (int) $order['id'] ?>)">
                                <?= esc((string) ($order['po_no'] ?? '')) ?>
                            </button>
                        </td>
                        <td><?= esc((string) ($order['due_date'] ?? '')) ?></td>
                        <td><?= esc(($order['payment_term'] ?? '') !== '' ? $order['payment_term'] . ' days' : '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) $order['total_amount'], 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['allocated_amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $order['balance'], 2)) ?></td>
                        <td class="text-center">
                            <div class="flex flex-wrap justify-center gap-2">
                                <button class="btn btn-secondary btn-strong" type="button" @click="openQuickPay(<?= (int) $order['id'] ?>)" <?= (float) $order['balance'] > 0 ? '' : 'disabled' ?>>Pay</button>
                                <button class="btn btn-secondary" type="button" @click="openEdit(<?= (int) $order['id'] ?>)" <?= (float) ($order['allocated_amount'] ?? 0) > 0 ? 'disabled' : '' ?>>Edit</button>
                                <button class="btn btn-secondary" type="button" @click="openVoid(<?= (int) $order['id'] ?>)" <?= ((float) ($order['allocated_amount'] ?? 0) <= 0 && (float) $order['balance'] > 0) ? '' : 'disabled' ?>>Void</button>
                                <button class="btn btn-secondary" type="button" @click="openHistory(<?= (int) $order['id'] ?>)">History</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (! empty($pagerLinks ?? '')): ?><div class="mt-4"><?= $pagerLinks ?></div><?php endif; ?>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="card p-4 total-highlight">
            <div class="flex justify-between"><span>Total Amount</span><span><?= esc(number_format((float) $totalAmount, 2)) ?></span></div>
        </div>
        <div class="card p-4 total-highlight">
            <div class="flex justify-between"><span>Total Balance</span><span><?= esc(number_format((float) $totalBalance, 2)) ?></span></div>
        </div>
    </div>

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

    <div class="modal-backdrop" x-show="poDetailsOpen" x-cloak @click.self="closePoDetails()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-4 border-b pb-4">
                <h2 class="text-lg font-semibold">RR Details: <span x-text="selectedPoNumber()"></span></h2>
            </div>
            <div class="modal-split">
                <div>
                    <h3 class="mb-3 font-semibold">Purchase Items</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Source PO</th>
                                <th>PO Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="selectedItems().length === 0">
                                <tr>
                                    <td class="py-3 text-center" colspan="6">No items found.</td>
                                </tr>
                            </template>
                            <template x-for="item in selectedItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name"></td>
                                    <td x-text="formatQty(item.qty)"></td>
                                    <td x-text="Number(item.unit_price).toFixed(2)"></td>
                                    <td x-text="Number(item.line_total).toFixed(2)"></td>
                                    <td x-text="item.supplier_order_po_no || ''"></td>
                                    <td x-text="item.po_qty_balance_after !== null && item.po_qty_balance_after !== undefined ? formatQty(item.po_qty_balance_after) : ''"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="mb-3 font-semibold">CV Allocations</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>CV #</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="selectedAllocations().length === 0">
                                <tr>
                                    <td class="py-3 text-center" colspan="3">No allocations found.</td>
                                </tr>
                            </template>
                            <template x-for="(alloc, index) in selectedAllocations()" :key="index">
                                <tr>
                                    <td x-text="alloc.pr_no"></td>
                                    <td x-text="alloc.date"></td>
                                    <td x-text="Number(alloc.amount).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6 flex justify-end"><button class="btn" type="button" @click="closePoDetails()">Close</button></div>
        </div>
    </div>

    <?= view('purchase_orders/_quick_pay_modal', ['quickPayData' => $quickPayData ?? []]) ?>

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

    <div class="modal-backdrop" x-show="voidOpen" x-cloak @click.self="closeVoid()">
        <div class="modal-panel max-w-xl p-6" @click.stop>
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Void RR / Pickup</h2>
                    <p class="mt-1 text-sm muted">Voiding cannot be undone.</p>
                </div><button class="btn btn-secondary" type="button" @click="closeVoid()">Close</button>
            </div>
            <form method="post" :action="selectedActionOrder() ? '<?= base_url('purchase-orders') ?>/' + selectedActionOrder().id + '/void' : '#'" class="space-y-4">
                <?= csrf_field() ?>
                <div class="card p-4 text-sm">
                    <div class="flex justify-between"><span>RR#</span><span x-text="selectedActionOrder() ? selectedActionOrder().po_no : ''"></span></div>
                    <div class="mt-2 flex justify-between"><span>Total</span><span x-text="formatAmount(selectedActionOrder() ? selectedActionOrder().total_amount : 0)"></span></div>
                    <div class="mt-2 flex justify-between"><span>Current Balance</span><span x-text="formatAmount(selectedActionOrder() ? selectedActionOrder().balance : 0)"></span></div>
                </div>
                <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">This will mark the RR as voided and insert a reversing payable ledger row.</div>
                <div><label class="block text-sm font-medium" for="void_reason">Reason</label><textarea class="input mt-1" id="void_reason" name="void_reason" rows="3" required></textarea></div>
                <div class="flex gap-3"><button class="btn" type="submit">Void RR / Pickup</button><button class="btn btn-secondary" type="button" @click="closeVoid()">Cancel</button></div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" x-show="historyOpen" x-cloak @click.self="closeHistory()">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="sticky top-0 z-10 -mx-6 -mt-6 border-b border-gray-200 bg-white p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">RR / Pickup History</h2>
                        <p class="mt-1 text-sm muted" x-text="selectedActionOrder() ? 'RR# ' + (selectedActionOrder().po_no || '-') + ' | Current total ' + formatAmount(selectedActionOrder().total_amount || 0) : ''"></p>
                    </div>
                    <button class="btn btn-secondary" type="button" @click="closeHistory()">Close</button>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <template x-if="selectedHistories().length === 0">
                    <div class="card p-4 text-sm">No history recorded for this RR yet.</div>
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
                                <p class="mt-2">RR#: <span x-text="historyOrder(history.old_purchase_order_json).po_no || '-'"></span></p>
                                <p>Date: <span x-text="historyOrder(history.old_purchase_order_json).date || '-'"></span></p>
                                <p>Total: <span x-text="formatAmount(historyOrder(history.old_purchase_order_json).total_amount || 0)"></span></p>
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
                                <p class="mt-2">RR#: <span x-text="historyOrder(history.new_purchase_order_json).po_no || '-'"></span></p>
                                <p>Date: <span x-text="historyOrder(history.new_purchase_order_json).date || '-'"></span></p>
                                <p>Total: <span x-text="formatAmount(historyOrder(history.new_purchase_order_json).total_amount || 0)"></span></p>
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
</div>

<script>
    function purchaseOrderList() {
        return {
            orders: <?= $ordersJson ?>,
            itemsByPurchaseOrder: <?= $itemsJson ?>,
            allocationsByPurchaseOrder: <?= $allocationsJson ?>,
            historiesByPurchaseOrder: <?= $historiesJson ?>,
            products: <?= $productsJson ?>,
            suppliers: <?= $suppliersJson ?>,
            orderFormOpen: <?= old('po_no') && ! old('purchase_order_id') ? 'true' : 'false' ?>,
            poDetailsOpen: false,
            quickPayOpen: <?= old('purchase_order_id') ? 'true' : 'false' ?>,
            editOpen: false,
            voidOpen: false,
            historyOpen: false,
            selectedOrderId: null,
            actionOrderId: null,
            quickPayOrderId: '<?= esc(old('purchase_order_id') ?: '') ?>',
            newOrder: {
                supplier_id: '<?= esc((string) ($selectedSupplier['id'] ?? old('supplier_id') ?? '')) ?>',
                po_no: '<?= esc(old('po_no') ?: '') ?>',
                date: '<?= esc(old('date') ?: date('Y-m-d')) ?>',
                payment_term: '<?= esc((string) $defaultPaymentTerm) ?>',
                due_date: '',
            },
            editOrder: {
                po_no: '',
                date: '',
                payment_term: '',
                due_date: ''
            },
            newItems: [{
                product_id: '',
                qty: 1,
                unit_price: '',
                line_total: '0.00'
            }],
            editItems: [],
            quickPay: {
                date: '<?= esc(old('date') && old('purchase_order_id') ? old('date') : date('Y-m-d')) ?>',
                method: '<?= esc(old('method') ?: 'cash') ?>',
                amountReceived: '<?= esc(old('amount_received')) ?>',
                depositBankId: '<?= esc(old('deposit_bank_id')) ?>',
                payerBank: '<?= esc(old('payer_bank')) ?>',
                checkNo: '<?= esc(old('check_no')) ?>',
                allocationAmount: '<?= esc(old('allocation_amount')) ?>',
                salesDiscount: '<?= esc(old('sales_discount')) ?>',
                deliveryCharges: '<?= esc(old('delivery_charges')) ?>',
                taxes: '<?= esc(old('taxes')) ?>',
                commissions: '<?= esc(old('commissions')) ?>',
                arOtherDescription: '',
                arOtherAmount: '0',
            },
            init() {
                this.recomputeDueDate(this.newOrder);
                this.applySupplierTerm();
            },
            openOrderForm() {
                this.orderFormOpen = true;
                this.poDetailsOpen = false;
                this.quickPayOpen = false;
            },
            applySupplierTerm() {
                if (this.newOrder.payment_term !== '') return;
                const selected = this.suppliers.find((row) => String(row.id) === String(this.newOrder.supplier_id));
                if (selected && selected.payment_term !== null && selected.payment_term !== undefined) {
                    this.newOrder.payment_term = selected.payment_term;
                    this.recomputeDueDate(this.newOrder);
                }
            },
            recomputeDueDate(order) {
                if (!order.date) {
                    order.due_date = '';
                    return;
                }
                const term = parseInt(order.payment_term, 10);
                const days = Number.isFinite(term) && term >= 0 ? term : 0;
                const date = new Date(order.date + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    order.due_date = '';
                    return;
                }
                date.setDate(date.getDate() + days);
                order.due_date = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            },
            addNewItem() {
                this.newItems.push({
                    product_id: '',
                    qty: 1,
                    unit_price: '',
                    line_total: '0.00'
                });
            },
            addEditItem() {
                this.editItems.push({
                    product_id: '',
                    qty: 1,
                    unit_price: '',
                    line_total: '0.00'
                });
            },
            selectProduct(items, index) {
                const item = items[index];
                const product = this.products.find((row) => String(row.id) === String(item.product_id));
                item.unit_price = product ? product.unit_price : '';
                this.updateLine(item);
            },
            updateLine(item) {
                item.line_total = ((parseFloat(item.qty) || 0) * (parseFloat(item.unit_price) || 0)).toFixed(2);
            },
            itemsTotal(items) {
                return items.reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0).toFixed(2);
            },
            formatAmount(value) {
                return (Math.round((parseFloat(value) || 0) * 100) / 100).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            formatQty(value) {
                return (Math.round((parseFloat(value) || 0) * 100000) / 100000).toLocaleString(undefined, {
                    minimumFractionDigits: 5,
                    maximumFractionDigits: 5
                });
            },
            syncAllocationFromReceived() {
                this.quickPay.allocationAmount = this.quickPay.amountReceived;
            },
            openPoDetails(id) {
                this.selectedOrderId = id;
                this.poDetailsOpen = true;
                this.quickPayOpen = false;
            },
            closePoDetails() {
                this.poDetailsOpen = false;
                this.selectedOrderId = null;
            },
            selectedOrder() {
                return this.orders.find((order) => String(order.id) === String(this.selectedOrderId)) || null;
            },
            selectedPoNumber() {
                const order = this.selectedOrder();
                return order ? order.po_no : '';
            },
            selectedItems() {
                return this.itemsByPurchaseOrder[this.selectedOrderId] || [];
            },
            selectedAllocations() {
                return this.allocationsByPurchaseOrder[this.selectedOrderId] || [];
            },
            selectedQuickPayOrder() {
                return this.orders.find((order) => String(order.id) === String(this.quickPayOrderId)) || null;
            },
            openQuickPay(id) {
                this.quickPayOrderId = id;
                const order = this.selectedQuickPayOrder();
                if (order) {
                    this.quickPay.amountReceived = (parseFloat(order.balance) || 0).toFixed(2);
                    this.quickPay.allocationAmount = (parseFloat(order.balance) || 0).toFixed(2);
                }
                this.quickPayOpen = true;
                this.orderFormOpen = false;
                this.poDetailsOpen = false;
            },
            closeQuickPay() {
                this.quickPayOpen = false;
                this.quickPayOrderId = '';
            },
            quickPayFixedAccountsTotal() {
                return [this.quickPay.salesDiscount, this.quickPay.deliveryCharges, this.quickPay.taxes, this.quickPay.commissions].reduce((sum, value) => sum + (parseFloat(value) || 0), 0);
            },
            quickPayBalanceAmount() {
                return (parseFloat(this.quickPay.amountReceived) || 0) + this.quickPayFixedAccountsTotal() - (parseFloat(this.quickPay.allocationAmount) || 0);
            },
            selectedActionOrder() {
                return this.orders.find((order) => String(order.id) === String(this.actionOrderId)) || null;
            },
            openEdit(id) {
                this.actionOrderId = id;
                const order = this.selectedActionOrder();
                if (!order) return;
                this.editOrder = {
                    po_no: order.po_no || '',
                    date: order.date || '',
                    payment_term: order.payment_term || '',
                    due_date: order.due_date || ''
                };
                this.editItems = (this.itemsByPurchaseOrder[id] || []).map((item) => ({
                    product_id: item.product_id,
                    qty: item.qty,
                    unit_price: item.unit_price,
                    line_total: (parseFloat(item.line_total) || 0).toFixed(2)
                }));
                if (this.editItems.length === 0) this.addEditItem();
                this.editOpen = true;
                this.quickPayOpen = false;
                this.poDetailsOpen = false;
            },
            closeEdit() {
                this.editOpen = false;
                this.actionOrderId = null;
            },
            openVoid(id) {
                this.actionOrderId = id;
                this.voidOpen = true;
                this.quickPayOpen = false;
            },
            closeVoid() {
                this.voidOpen = false;
                this.actionOrderId = null;
            },
            openHistory(id) {
                this.actionOrderId = id;
                this.historyOpen = true;
                this.quickPayOpen = false;
            },
            closeHistory() {
                this.historyOpen = false;
                this.actionOrderId = null;
            },
            selectedHistories() {
                return this.historiesByPurchaseOrder[this.actionOrderId] || [];
            },
            historyOrder(value) {
                if (!value) {
                    return {};
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    return {};
                }
            },
            historyItems(value) {
                if (!value) {
                    return [];
                }
                try {
                    const items = JSON.parse(value);
                    return Array.isArray(items) ? items : [];
                } catch (error) {
                    return [];
                }
            },
        };
    }
</script>
<?= $this->endSection() ?>