<?php
/**
 * @var array<string, int|float|string|null> $supplierOrder
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
$printParams = [
    'from_date' => $fromDate ?? '',
    'to_date' => $toDate ?? '',
];
?>

<div x-data="supplierPoLedger()" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">PO Ledger: <?= esc((string) ($supplierOrder['po_no'] ?? '')) ?></h1>
            <p class="mt-1 text-sm muted"><?= esc((string) ($supplierOrder['supplier_name'] ?? '')) ?> | Ordered <?= esc(number_format((float) ($orderedTotal ?? 0), 2)) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/supplier-orders') ?>">PO</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/purchase-orders') ?>">Pickup</a>
            <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . $supplierId) ?>">Payments</a>
            <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . $supplierId) ?>">Supplier Ledger</a>
            <a class="btn btn-secondary" target="_blank" href="<?= base_url('supplier-orders/' . $supplierOrderId . '/ledger/print') ?>?<?= esc(http_build_query($printParams)) ?>">Print</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/supplier-orders') ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= base_url('supplier-orders/' . $supplierOrderId . '/ledger') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="block text-sm font-medium" for="from_date">From Date</label>
                <input class="input mt-1" id="from_date" name="from_date" type="date" value="<?= esc($fromDate ?? '') ?>" @change="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="to_date">To Date</label>
                <input class="input mt-1" id="to_date" name="to_date" type="date" value="<?= esc($toDate ?? '') ?>" @change="$el.form.requestSubmit()">
            </div>
            <div class="flex items-end gap-2">
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
                    <td class="text-right"><?= ($row['qty'] ?? null) === null ? '' : esc(number_format((float) ($row['qty'] ?? 0), 2)) ?></td>
                    <td class="text-right"><?= esc(number_format((float) ($row['po_balance'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card p-4">
            <p class="muted">Ordered Qty</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($orderedTotal ?? 0), 2)) ?></p>
        </div>
        <div class="card p-4">
            <p class="muted">Ending PO Balance</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($endingBalance ?? 0), 2)) ?></p>
        </div>
    </div>

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
                        <thead><tr><th>Product</th><th class="text-right">Purchase Qty</th><th class="text-right">Picked-Up</th><th class="text-right">Balance</th></tr></thead>
                        <tbody>
                            <template x-if="supplierItems().length === 0"><tr><td colspan="4">No items found.</td></tr></template>
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
                            <thead><tr><th>RR#</th><th>Date</th><th>Product</th><th class="text-right">Qty</th></tr></thead>
                            <tbody>
                                <template x-if="supplierConsumptions().length === 0"><tr><td colspan="4">No RR consumption found.</td></tr></template>
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
                        <thead><tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            <template x-if="rrItems().length === 0"><tr><td colspan="4">No items found.</td></tr></template>
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
                        <thead><tr><th>CV#</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            <template x-if="rrAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template>
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
</div>

<script>
    function supplierPoLedger() {
        return {
            supplierOrderOpen: false,
            rrOpen: false,
            supplierOrderDetail: {},
            rrDetail: {},
            async openSupplierOrder(id) {
                const response = await fetch(`<?= base_url('ajax/supplier-orders') ?>/${id}`);
                this.supplierOrderDetail = response.ok ? await response.json() : {};
                this.supplierOrderOpen = true;
            },
            closeSupplierOrder() { this.supplierOrderOpen = false; this.supplierOrderDetail = {}; },
            async openRr(id) {
                const response = await fetch(`<?= base_url('ajax/purchase-orders') ?>/${id}`);
                this.rrDetail = response.ok ? await response.json() : {};
                this.rrOpen = true;
            },
            closeRr() { this.rrOpen = false; this.rrDetail = {}; },
            supplierPoNumber() { return this.supplierOrderDetail.supplier_order ? this.supplierOrderDetail.supplier_order.po_no || '' : ''; },
            rrNumber() { return this.rrDetail.purchase_order ? this.rrDetail.purchase_order.po_no || '' : ''; },
            supplierItems() { return this.supplierOrderDetail.items || []; },
            supplierConsumptions() { return this.supplierOrderDetail.consumptions || []; },
            rrItems() { return this.rrDetail.items || []; },
            rrAllocations() { return this.rrDetail.allocations || []; },
            formatQty(value) { return (Math.round((parseFloat(value) || 0) * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            formatAmount(value) { return (parseFloat(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    }
</script>
<?= $this->endSection() ?>
