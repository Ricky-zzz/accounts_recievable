<?php

/**
 * @var array<string, int|float|string|null> $supplierOrder
 * @var array<string, mixed> $pickupFormData
 * @var array<string, mixed> $quickPayData
 * @var string $fromDate
 * @var string $toDate
 * @var int|float|string $orderedTotal
 * @var int|float|string $endingBalance
 * @var list<array<string, int|float|string|null>> $rows
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$supplierOrderId = (int) ($supplierOrder['id'] ?? 0);
$supplierId = (int) ($supplierOrder['supplier_id'] ?? 0);
$pickupItems = $pickupFormData['items'] ?? [];
$pickupItemsJson = json_encode($pickupItems, $jsonFlags);
$defaultPickupItemId = (string) ($pickupItems[0]['id'] ?? '');
$defaultPaymentTerm = old('payment_term') ?? ($supplierOrder['supplier_payment_term'] ?? '');
$quickPayOrders = [];
foreach ($rows as $row) {
    $purchaseOrderId = (int) ($row['purchase_order_id'] ?? 0);
    if (($row['type'] ?? '') !== 'movement' || $purchaseOrderId <= 0) {
        continue;
    }

    $quickPayOrders[] = [
        'id' => $purchaseOrderId,
        'supplier_id' => (int) ($row['supplier_id'] ?? $supplierId),
        'supplier_name' => (string) ($row['supplier_name'] ?? ($supplierOrder['supplier_name'] ?? '')),
        'po_no' => (string) ($row['rr_no'] ?? ''),
        'date' => (string) ($row['date'] ?? ''),
        'total_amount' => (float) ($row['total_amount'] ?? 0),
        'allocated_amount' => (float) ($row['allocated_amount'] ?? 0),
        'balance' => (float) ($row['balance'] ?? 0),
    ];
}
$quickPayOrdersJson = json_encode($quickPayOrders, $jsonFlags);
$printParams = [
    'from_date' => $fromDate ?? '',
    'to_date' => $toDate ?? '',
];
?>

<div x-data="supplierPoLedger()" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">PO Ledger: <?= esc((string) ($supplierOrder['po_no'] ?? '')) ?></h1>
            <p class="mt-1 text-sm muted"><?= esc((string) ($supplierOrder['supplier_name'] ?? '')) ?> | Ordered <?= esc(number_format((float) ($orderedTotal ?? 0), 5)) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/supplier-orders') ?>">PO</a>
            <button class="btn btn-strong" type="button" @click="openPickupForm()">New Pickup</button>
            <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . $supplierId) ?>">Payments</a>
            <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . $supplierId) ?>">Supplier Ledger</a>
            <a class="btn btn-secondary" target="_blank" href="<?= base_url('supplier-orders/' . $supplierOrderId . '/ledger/print') ?>?<?= esc(http_build_query($printParams)) ?>">Print</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/supplier-orders') ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= base_url('supplier-orders/' . $supplierOrderId . '/ledger') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
        <input type="hidden" name="from_date" x-ref="fromDate" value="<?= esc($fromDate ?? '') ?>">
        <input type="hidden" name="to_date" x-ref="toDate" value="<?= esc($toDate ?? '') ?>">
        <div class="grid gap-4 md:grid-cols-3">
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
                <a class="btn btn-secondary" href="<?= base_url('supplier-orders/' . $supplierOrderId . '/ledger') ?>">Clear</a>
            </div>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>PO</th>
                <th>RR</th>
                <th class="text-right">Qty</th>
                <th class="text-right">PO Balance</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                    <td>
                        <button class="btn-link" type="button" @click="openSupplierOrder(<?= $supplierOrderId ?>)">
                            <?= esc((string) ($row['po_no'] ?? '')) ?>
                        </button>
                    </td>
                    <td>
                        <?php if (! empty($row['purchase_order_id'])): ?>
                            <button class="btn-link" type="button" @click="openRr(<?= (int) $row['purchase_order_id'] ?>)">
                                <?= esc((string) ($row['rr_no'] ?? '')) ?>
                            </button>
                        <?php else: ?>
                            <?= esc((string) ($row['rr_no'] ?? '')) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?= ($row['qty'] ?? null) === null ? '' : esc(number_format((float) ($row['qty'] ?? 0), 5)) ?></td>
                    <td class="text-right"><?= esc(number_format((float) ($row['po_balance'] ?? 0), 5)) ?></td>
                    <td class="text-center">
                        <?php if (! empty($row['purchase_order_id'])): ?>
                            <button class="btn btn-secondary btn-strong" type="button" @click="openQuickPay(<?= (int) $row['purchase_order_id'] ?>)" <?= (float) ($row['balance'] ?? 0) > 0 ? '' : 'disabled' ?>>Pay</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card p-4">
            <p class="muted">Ordered Qty</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($orderedTotal ?? 0), 5)) ?></p>
        </div>
        <div class="card p-4">
            <p class="muted">Ending PO Balance</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($endingBalance ?? 0), 5)) ?></p>
        </div>
    </div>

    <?= view('supplier_order_ledger/_pickup_modal', [
        'supplierOrder' => $supplierOrder,
        'pickupItems' => $pickupItems,
        'supplierId' => $supplierId,
        'supplierOrderId' => $supplierOrderId,
    ]) ?>

    <div class="modal-backdrop" x-show="supplierOrderOpen" x-cloak @click.self="closeSupplierOrder()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-5 flex items-start justify-between gap-4 border-b pb-4">
                <div>
                    <h2 class="text-lg font-semibold">PO Details: <span x-text="supplierPoNumber()"></span></h2>
                    <p class="mt-1 text-sm muted" x-text="supplierOrderDetail.supplier_order ? supplierOrderDetail.supplier_order.supplier_name || '' : ''"></p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeSupplierOrder()">Close</button>
            </div>
            <div class="space-y-5">
                <section>
                    <h3 class="mb-3 text-sm font-semibold">PO Items</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Purchase Qty</th>
                                <th class="text-right">Picked-Up</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="supplierItems().length === 0">
                                <tr>
                                    <td colspan="4">No items found.</td>
                                </tr>
                            </template>
                            <template x-for="item in supplierItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name || '-'"></td>
                                    <td class="text-right" x-text="formatQty(item.qty_ordered)"></td>
                                    <td class="text-right" x-text="formatQty(item.qty_picked_up)"></td>
                                    <td class="text-right" x-text="formatQty(item.qty_balance)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </section>
                <section>
                    <h3 class="mb-3 text-sm font-semibold">RR Consumption</h3>
                    <div class="overflow-y-auto rounded border border-gray-200" style="max-height: 45vh;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>RR#</th>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th class="text-right">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="supplierConsumptions().length === 0">
                                    <tr>
                                        <td colspan="4">No RR consumption found.</td>
                                    </tr>
                                </template>
                                <template x-for="(item, index) in supplierConsumptions()" :key="index">
                                    <tr>
                                        <td><button class="btn-link" type="button" @click="openRr(item.purchase_order_id)" x-text="item.rr_no || '-'"></button></td>
                                        <td x-text="item.rr_date || '-'"></td>
                                        <td x-text="item.product_name || '-'"></td>
                                        <td class="text-right" x-text="formatQty(item.qty)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="rrOpen" x-cloak @click.self="closeRr()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-5 flex items-start justify-between gap-4 border-b pb-4">
                <div>
                    <h2 class="text-lg font-semibold">RR Details: <span x-text="rrNumber()"></span></h2>
                    <p class="mt-1 text-sm muted" x-text="rrDetail.purchase_order ? rrDetail.purchase_order.supplier_name || '' : ''"></p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeRr()">Close</button>
            </div>
            <div class="modal-split">
                <div>
                    <h3 class="mb-3 font-semibold">Pickup Items</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="rrItems().length === 0">
                                <tr>
                                    <td colspan="4">No items found.</td>
                                </tr>
                            </template>
                            <template x-for="item in rrItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name || '-'"></td>
                                    <td class="text-right" x-text="formatQty(item.qty)"></td>
                                    <td class="text-right" x-text="formatAmount(item.unit_price)"></td>
                                    <td class="text-right" x-text="formatAmount(item.line_total)"></td>
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
                                <th>CV#</th>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="rrAllocations().length === 0">
                                <tr>
                                    <td colspan="3">No allocations found.</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in rrAllocations()" :key="index">
                                <tr>
                                    <td x-text="item.pr_no || '-'"></td>
                                    <td x-text="item.date || '-'"></td>
                                    <td class="text-right" x-text="formatAmount(item.amount)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?= view('purchase_orders/_quick_pay_modal', ['quickPayData' => $quickPayData ?? []]) ?>
</div>

<script>
    function supplierPoLedger() {
        return {
            pickupFormOpen: <?= old('return_to_supplier_order_ledger') ? 'true' : 'false' ?>,
            supplierOrderOpen: false,
            rrOpen: false,
            quickPayOpen: <?= old('purchase_order_id') ? 'true' : 'false' ?>,
            pickupItems: <?= $pickupItemsJson ?>,
            quickPayOrders: <?= $quickPayOrdersJson ?>,
            pickupForm: {
                supplier_order_item_id: '<?= esc(old('items.0.supplier_order_item_id') ?: $defaultPickupItemId) ?>',
                po_no: '<?= esc(old('po_no') ?: '') ?>',
                date: '<?= esc(old('date') ?: date('Y-m-d')) ?>',
                payment_term: '<?= esc((string) $defaultPaymentTerm) ?>',
                due_date: '',
                qty: '<?= esc(old('items.0.qty') ?: '') ?>',
                unit_price: '<?= esc(old('items.0.unit_price') ?: '') ?>',
            },
            quickPayOrderId: '<?= esc(old('purchase_order_id') ?: '') ?>',
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
            supplierOrderDetail: {},
            rrDetail: {},
            init() {
                this.syncPickupItem();
                this.recomputePickupDueDate();
            },
            openPickupForm() {
                this.pickupFormOpen = true;
                this.supplierOrderOpen = false;
                this.rrOpen = false;
            },
            closePickupForm() {
                this.pickupFormOpen = false;
            },
            selectedPickupItem() {
                return this.pickupItems.find((item) => String(item.id) === String(this.pickupForm.supplier_order_item_id)) || null;
            },
            syncPickupItem() {
                const item = this.selectedPickupItem();
                if (item && this.pickupForm.unit_price === '') {
                    this.pickupForm.unit_price = item.unit_price || '';
                }
            },
            recomputePickupDueDate() {
                if (!this.pickupForm.date) {
                    this.pickupForm.due_date = '';
                    return;
                }
                const term = parseInt(this.pickupForm.payment_term, 10);
                const days = Number.isFinite(term) && term >= 0 ? term : 0;
                const date = new Date(this.pickupForm.date + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    this.pickupForm.due_date = '';
                    return;
                }
                date.setDate(date.getDate() + days);
                this.pickupForm.due_date = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            },
            pickupLineTotal() {
                return (parseFloat(this.pickupForm.qty) || 0) * (parseFloat(this.pickupForm.unit_price) || 0);
            },
            selectedQuickPayOrder() {
                return this.quickPayOrders.find((order) => String(order.id) === String(this.quickPayOrderId)) || null;
            },
            openQuickPay(id) {
                this.quickPayOrderId = id;
                const order = this.selectedQuickPayOrder();
                if (order) {
                    this.quickPay.amountReceived = (parseFloat(order.balance) || 0).toFixed(2);
                    this.quickPay.allocationAmount = (parseFloat(order.balance) || 0).toFixed(2);
                }
                this.quickPayOpen = true;
                this.pickupFormOpen = false;
                this.supplierOrderOpen = false;
                this.rrOpen = false;
            },
            closeQuickPay() {
                this.quickPayOpen = false;
                this.quickPayOrderId = '';
            },
            syncAllocationFromReceived() {
                this.quickPay.allocationAmount = this.quickPay.amountReceived;
            },
            quickPayFixedAccountsTotal() {
                return [this.quickPay.salesDiscount, this.quickPay.deliveryCharges, this.quickPay.taxes, this.quickPay.commissions].reduce((sum, value) => sum + (parseFloat(value) || 0), 0);
            },
            quickPayBalanceAmount() {
                return (parseFloat(this.quickPay.amountReceived) || 0) + this.quickPayFixedAccountsTotal() - (parseFloat(this.quickPay.allocationAmount) || 0);
            },
            async openSupplierOrder(id) {
                const response = await fetch(`<?= base_url('ajax/supplier-orders') ?>/${id}`);
                this.supplierOrderDetail = response.ok ? await response.json() : {};
                this.supplierOrderOpen = true;
            },
            closeSupplierOrder() {
                this.supplierOrderOpen = false;
                this.supplierOrderDetail = {};
            },
            async openRr(id) {
                const response = await fetch(`<?= base_url('ajax/purchase-orders') ?>/${id}`);
                this.rrDetail = response.ok ? await response.json() : {};
                this.rrOpen = true;
            },
            closeRr() {
                this.rrOpen = false;
                this.rrDetail = {};
            },
            supplierPoNumber() {
                return this.supplierOrderDetail.supplier_order ? this.supplierOrderDetail.supplier_order.po_no || '' : '';
            },
            rrNumber() {
                return this.rrDetail.purchase_order ? this.rrDetail.purchase_order.po_no || '' : '';
            },
            supplierItems() {
                return this.supplierOrderDetail.items || [];
            },
            supplierConsumptions() {
                return this.supplierOrderDetail.consumptions || [];
            },
            rrItems() {
                return this.rrDetail.items || [];
            },
            rrAllocations() {
                return this.rrDetail.allocations || [];
            },
            formatQty(value) {
                return (Math.round((parseFloat(value) || 0) * 100000) / 100000).toLocaleString(undefined, {
                    minimumFractionDigits: 5,
                    maximumFractionDigits: 5
                });
            },
            formatAmount(value) {
                return (parseFloat(value) || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
        };
    }
</script>
<?= $this->endSection() ?>
