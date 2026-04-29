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
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsByPurchaseOrder ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByPurchaseOrder ?? [], $jsonFlags);
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

        <form method="get" action="<?= base_url('payable-reports/voided') ?>" class="grid gap-4 md:grid-cols-5">
            <div>
                <label class="block text-sm font-medium" for="po_no">PO Number</label>
                <input class="input mt-1" id="po_no" name="po_no" value="<?= esc($poNo ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium" for="from_voided_date">From Voided Date</label>
                <input class="input mt-1" id="from_voided_date" name="from_voided_date" type="date" value="<?= esc($fromVoidedDate ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium" for="to_voided_date">To Voided Date</label>
                <input class="input mt-1" id="to_voided_date" name="to_voided_date" type="date" value="<?= esc($toVoidedDate ?? '') ?>">
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <button class="btn btn-secondary" type="submit">Filter</button>
                <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided') ?>">Clear</a>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9">No voided purchase orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                            <td>
                                <button class="btn-link" type="button" @click="openPoDetails(<?= (int) ($row['id'] ?? 0) ?>)">
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
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
</div>

<script>
    function voidedPayableReport() {
        return {
            itemsByPurchaseOrder: <?= $itemsJson ?>,
            allocationsByPurchaseOrder: <?= $allocationsJson ?>,
            rows: <?= $rowsJson ?>,
            poDetailsOpen: false,
            selectedPurchaseOrderId: null,
            openPoDetails(id) { this.selectedPurchaseOrderId = id; this.poDetailsOpen = true; },
            closePoDetails() { this.poDetailsOpen = false; this.selectedPurchaseOrderId = null; },
            selectedPoNumber() {
                const row = this.rows.find((r) => String(r.id) === String(this.selectedPurchaseOrderId));
                return row ? row.po_no : '';
            },
            selectedItems() { return this.itemsByPurchaseOrder[this.selectedPurchaseOrderId] || []; },
            selectedAllocations() { return this.allocationsByPurchaseOrder[this.selectedPurchaseOrderId] || []; },
            itemsTotal() { return this.selectedItems().reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0).toFixed(2); },
            allocationsTotal() { return this.selectedAllocations().reduce((sum, alloc) => sum + (parseFloat(alloc.amount) || 0), 0).toFixed(2); },
        };
    }
</script>
<?= $this->endSection() ?>
