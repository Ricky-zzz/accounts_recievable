<?php

/**
 * @var array{id: int|string, name: string}|null $supplier
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
$listUrl = base_url('suppliers/' . ($supplier['id'] ?? 0) . '/purchase-orders');
$printParams = ['from_date' => $fromDate ?? '', 'to_date' => $toDate ?? '', 'po_no' => $poNo ?? ''];
$printParams['supplier_id'] = $supplier['id'] ?? 0;
?>

<div x-data="purchaseOrderList()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">
                RR / Pickups for <?= esc((string) ($supplier['name'] ?? '')) ?>
            </h1>
            <p class="mt-1 text-sm muted">Filter pickups by date range or RR number.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button class="btn btn-strong" type="button" @click="openOrderForm()">New Pickup</button>
            <a class="btn btn-secondary" href="<?= base_url('suppliers/' . ($supplier['id'] ?? 0) . '/supplier-orders') ?>">PO</a>
            <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . ($supplier['id'] ?? 0)) ?>">Ledger</a>
            <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . ($supplier['id'] ?? 0)) ?>">Payments</a>
            <button class="btn btn-secondary" type="button" @click='openSupplierStatementModal(<?= (int) ($supplier['id'] ?? 0) ?>, <?= json_encode((string) ($supplier['name'] ?? ''), $jsonFlags) ?>, <?= json_encode((string) ($supplier['payment_term'] ?? ''), $jsonFlags) ?>)'>Payables</button>
            <a class="btn btn-secondary" target="_blank" href="<?= base_url('purchase-orders/print') ?>?<?= esc(http_build_query($printParams)) ?>">Print</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>?q=<?= rawurlencode((string) ($supplier['name'] ?? '')) ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= esc($listUrl) ?>" class="filter-card mt-4 rounded border border-gray-200 p-4" x-data>
        <input type="hidden" name="from_date" x-ref="fromDate" value="<?= esc($fromDate ?? '') ?>">
        <input type="hidden" name="to_date" x-ref="toDate" value="<?= esc($toDate ?? '') ?>">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium" for="po_no">RR Number</label>
                <input class="input mt-1" id="po_no" name="po_no" value="<?= esc($poNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="from_date">From Date</label>
                <input class="input mt-1" id="from_date" x-ref="fromDateDraft" type="date" value="<?= esc($fromDate ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium" for="to_date">To Date</label>
                <input class="input mt-1" id="to_date" x-ref="toDateDraft" type="date" value="<?= esc($toDate ?? '') ?>">
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-strong" type="submit" @click="$refs.fromDate.value = $refs.fromDateDraft.value; $refs.toDate.value = $refs.toDateDraft.value">Filter</button>
                <a class="btn btn-secondary" href="<?= esc($listUrl) ?>">Clear</a>
            </div>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
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
                    <td class="py-3" colspan="9">No pickups found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchaseOrders as $index => $order): ?>
                    <tr>
                        <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                        <td><?= esc((string) $order['date']) ?></td>
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

    <?= view('purchase_orders/_create_modal', [
        'selectedSupplier' => $selectedSupplier,
        'suppliers' => $suppliers,
        'products' => $products,
    ]) ?>

    <?= view('components/transaction_details/purchase_order_modal') ?>

    <?= view('purchase_orders/_quick_pay_modal', ['quickPayData' => $quickPayData ?? []]) ?>

    <?= view('purchase_orders/_edit_modal', [
        'products' => $products,
    ]) ?>

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

    <?= view('suppliers/_statement_modal') ?>
</div>

<script>
    function purchaseOrderList() {
        return {
            ...supplierStatementModalState(),
            ...transactionDetailsState({
                endpoints: {
                    purchaseOrder: '<?= base_url('ajax/purchase-orders') ?>',
                },
            }),
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
                const order = this.orders.find((row) => String(row.id) === String(id));
                this.openDetail('purchaseOrder', id, order ? (order.po_no || '') : '');
                this.quickPayOpen = false;
            },
            closePoDetails() {
                this.closeDetail('purchaseOrder');
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
