<?php
/**
 * @var string $fromVoidedDate
 * @var string $toVoidedDate
 * @var string $poNo
 * @var list<array{id?: int|string, date?: string|null, po_no?: string|null, supplier_name?: string|null, due_date?: string|null, total_amount?: int|float|string|null, balance?: int|float|string|null, voided_at?: string|null, void_reason?: string|null}> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $historiesByPurchaseOrder
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsByPurchaseOrder ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByPurchaseOrder ?? [], $jsonFlags);
$historiesJson = json_encode($historiesByPurchaseOrder ?? [], $jsonFlags);
$rowsJson = json_encode($rows ?? [], $jsonFlags);
$filterQuery = [
    'po_no' => $poNo ?? '',
    'from_voided_date' => $fromVoidedDate ?? '',
    'to_voided_date' => $toVoidedDate ?? '',
];
?>
<div x-data="voidedPayableReport()">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Voided Purchase Orders</h1>
                <p class="mt-1 text-sm muted">Latest voided purchase orders with reasons and balances.</p>
            </div>
            <a class="btn" href="<?= base_url('payable-reports/voided/print?' . http_build_query($filterQuery)) ?>" target="_blank">Print</a>
        </div>

        <form method="get" action="<?= base_url('payable-reports/voided') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
            <div class="grid gap-4 md:grid-cols-5">
            <div>
                <label class="block text-sm font-medium" for="po_no">PO Number</label>
                <input class="input mt-1" id="po_no" name="po_no" value="<?= esc($poNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="from_voided_date">From Voided Date</label>
                <input class="input mt-1" id="from_voided_date" name="from_voided_date" type="date" value="<?= esc($fromVoidedDate ?? '') ?>" @change="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="to_voided_date">To Voided Date</label>
                <input class="input mt-1" id="to_voided_date" name="to_voided_date" type="date" value="<?= esc($toVoidedDate ?? '') ?>" @change="$el.form.requestSubmit()">
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided') ?>">Clear</a>
            </div>
            </div>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>PO #</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Voided At</th>
                    <th>Supplier</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Balance</th>
                    <th>Reason</th>
                    <th class="text-center">History</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10">No voided purchase orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php $rowId = (int) ($row['id'] ?? 0); ?>
                        <tr>
                            <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                            <td>
                                <button class="btn-link" type="button" @click="openPoDetails(<?= $rowId ?>)">
                                    <?= esc((string) ($row['po_no'] ?? '')) ?>
                                </button>
                            </td>
                            <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['due_date'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['voided_at'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['supplier_name'] ?? '')) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                            <td><?= esc((string) ($row['void_reason'] ?? '')) ?></td>
                            <td class="text-center">
                                <?php if (! empty($historiesByPurchaseOrder[$rowId])): ?>
                                    <button class="btn btn-secondary" type="button" @click="openHistory(<?= $rowId ?>)">History</button>
                                <?php else: ?>
                                    <span class="muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-highlight">
                    <th colspan="6">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="flex items-center justify-between gap-4 text-sm muted">
                <div>Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?></div>
                <div class="flex items-center gap-2">
                    <?php if (($currentPage ?? 1) > 1): ?>
                        <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided?' . http_build_query($filterQuery + ['page' => $currentPage - 1])) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                        <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided?' . http_build_query($filterQuery + ['page' => $currentPage + 1])) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-backdrop" x-show="poDetailsOpen" x-cloak @click.self="closePoDetails()">
        <div class="modal-panel max-w-4xl p-6" @click.stop>
            <div class="mb-4 border-b pb-4">
                <h2 class="text-lg font-semibold">Details for PO#: <span x-text="selectedPoNumber()"></span></h2>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="mb-3 font-semibold">Purchase Items</h3>
                    <table class="table">
                        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                        <tbody>
                            <template x-if="selectedItems().length === 0"><tr><td class="py-3 text-center" colspan="4">No items found.</td></tr></template>
                            <template x-for="item in selectedItems()" :key="item.id"><tr><td x-text="item.product_name"></td><td x-text="item.qty"></td><td x-text="Number(item.unit_price).toFixed(2)"></td><td x-text="Number(item.line_total).toFixed(2)"></td></tr></template>
                        </tbody>
                    </table>
                    <div class="mt-2 text-sm font-semibold" x-show="selectedItems().length > 0">Total: <span x-text="itemsTotal()"></span></div>
                </div>
                <div>
                    <h3 class="mb-3 font-semibold">PO Allocations</h3>
                    <table class="table">
                        <thead><tr><th>PR #</th><th>Date</th><th>Amount</th></tr></thead>
                        <tbody>
                            <template x-if="selectedAllocations().length === 0"><tr><td class="py-3 text-center" colspan="3">No allocations found.</td></tr></template>
                            <template x-for="(alloc, index) in selectedAllocations()" :key="index"><tr><td x-text="alloc.pr_no"></td><td x-text="alloc.date"></td><td x-text="Number(alloc.amount).toFixed(2)"></td></tr></template>
                        </tbody>
                    </table>
                    <div class="mt-2 text-sm font-semibold" x-show="selectedAllocations().length > 0">Total: <span x-text="allocationsTotal()"></span></div>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end"><button class="btn" type="button" @click="closePoDetails()">Close</button></div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="historyOpen" x-cloak @click.self="closeHistory()">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="sticky top-0 z-10 -mx-6 -mt-6 border-b border-gray-200 bg-white p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Purchase Order History</h2>
                        <p class="mt-1 text-sm muted" x-text="selectedHistoryOrder() ? 'PO# ' + (selectedHistoryOrder().po_no || '-') + ' | Current total ' + formatAmount(selectedHistoryOrder().total_amount || 0) : ''"></p>
                    </div>
                    <button class="btn btn-secondary" type="button" @click="closeHistory()">Close</button>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <template x-if="selectedHistories().length === 0">
                    <div class="card p-4 text-sm">No history recorded for this purchase order yet.</div>
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
                                <p class="mt-2">PO#: <span x-text="historyOrder(history.old_purchase_order_json).po_no || '-'" ></span></p>
                                <p>Date: <span x-text="historyOrder(history.old_purchase_order_json).date || '-'" ></span></p>
                                <p>Total: <span x-text="formatAmount(historyOrder(history.old_purchase_order_json).total_amount || 0)" ></span></p>
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
                                                    <td x-text="item.qty"></td>
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
                                <p class="mt-2">PO#: <span x-text="historyOrder(history.new_purchase_order_json).po_no || '-'" ></span></p>
                                <p>Date: <span x-text="historyOrder(history.new_purchase_order_json).date || '-'" ></span></p>
                                <p>Total: <span x-text="formatAmount(historyOrder(history.new_purchase_order_json).total_amount || 0)" ></span></p>
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
                                                    <td x-text="item.qty"></td>
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
    function voidedPayableReport() {
        return {
            itemsByPurchaseOrder: <?= $itemsJson ?>,
            allocationsByPurchaseOrder: <?= $allocationsJson ?>,
            historiesByPurchaseOrder: <?= $historiesJson ?>,
            rows: <?= $rowsJson ?>,
            poDetailsOpen: false,
            historyOpen: false,
            selectedPurchaseOrderId: null,
            selectedHistoryId: null,
            normalizeAmount(value) {
                return Math.round((parseFloat(value) || 0) * 100) / 100;
            },
            formatAmount(value) {
                return this.normalizeAmount(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
            openPoDetails(id) { this.selectedPurchaseOrderId = id; this.poDetailsOpen = true; },
            closePoDetails() { this.poDetailsOpen = false; this.selectedPurchaseOrderId = null; },
            openHistory(id) { this.selectedHistoryId = id; this.historyOpen = true; },
            closeHistory() { this.historyOpen = false; this.selectedHistoryId = null; },
            selectedPoNumber() {
                const row = this.rows.find((r) => String(r.id) === String(this.selectedPurchaseOrderId));
                return row ? row.po_no : '';
            },
            selectedHistoryOrder() {
                return this.rows.find((r) => String(r.id) === String(this.selectedHistoryId)) || null;
            },
            selectedHistories() { return this.historiesByPurchaseOrder[this.selectedHistoryId] || []; },
            selectedItems() { return this.itemsByPurchaseOrder[this.selectedPurchaseOrderId] || []; },
            selectedAllocations() { return this.allocationsByPurchaseOrder[this.selectedPurchaseOrderId] || []; },
            itemsTotal() { return this.selectedItems().reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0).toFixed(2); },
            allocationsTotal() { return this.selectedAllocations().reduce((sum, alloc) => sum + (parseFloat(alloc.amount) || 0), 0).toFixed(2); },
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
