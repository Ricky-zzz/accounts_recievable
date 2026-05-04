<?php
/**
 * @var string $fromVoidedDate
 * @var string $toVoidedDate
 * @var string $poNo
 * @var list<array{id?: int|string, date?: string|null, po_no?: string|null, supplier_name?: string|null, qty_ordered_total?: int|float|string|null, qty_picked_up_total?: int|float|string|null, qty_balance_total?: int|float|string|null, voided_at?: string|null, void_reason?: string|null}> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 * @var int|float|string $totalOrdered
 * @var int|float|string $totalPickedUp
 * @var int|float|string $totalBalance
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsBySupplierOrder
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsBySupplierOrder ?? [], $jsonFlags);
$rowsJson = json_encode($rows ?? [], $jsonFlags);
$filterQuery = [
    'po_no' => $poNo ?? '',
    'from_voided_date' => $fromVoidedDate ?? '',
    'to_voided_date' => $toVoidedDate ?? '',
];
?>
<div x-data="voidedPosReport()">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Voided POs</h1>
                <p class="mt-1 text-sm muted">Voided supplier purchase orders with reserved quantities.</p>
            </div>
            <a class="btn" href="<?= base_url('payable-reports/voided-pos/print?' . http_build_query($filterQuery)) ?>" target="_blank">Print</a>
        </div>

        <form method="get" action="<?= base_url('payable-reports/voided-pos') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
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
                    <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided-pos') ?>">Clear</a>
                </div>
            </div>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>PO #</th>
                    <th>Date</th>
                    <th>Voided At</th>
                    <th>Supplier</th>
                    <th class="text-right">Ordered Qty</th>
                    <th class="text-right">Picked Qty</th>
                    <th class="text-right">Balance Qty</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9">No voided POs found.</td></tr>
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
                            <td><?= esc((string) ($row['voided_at'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['supplier_name'] ?? '')) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['qty_ordered_total'] ?? 0), 2)) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['qty_picked_up_total'] ?? 0), 2)) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['qty_balance_total'] ?? 0), 2)) ?></td>
                            <td><?= esc((string) ($row['void_reason'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-highlight">
                    <th colspan="5">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalOrdered ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totalPickedUp ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totalBalance ?? 0), 2)) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="flex items-center justify-between gap-4 text-sm muted">
                <div>Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?></div>
                <div class="flex items-center gap-2">
                    <?php if (($currentPage ?? 1) > 1): ?>
                        <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided-pos?' . http_build_query($filterQuery + ['page' => $currentPage - 1])) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                        <a class="btn btn-secondary" href="<?= base_url('payable-reports/voided-pos?' . http_build_query($filterQuery + ['page' => $currentPage + 1])) ?>">Next</a>
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
            <table class="table">
                <thead><tr><th>Product</th><th class="text-right">Ordered</th><th class="text-right">Picked</th><th class="text-right">Balance</th></tr></thead>
                <tbody>
                    <template x-if="selectedItems().length === 0"><tr><td class="py-3 text-center" colspan="4">No items found.</td></tr></template>
                    <template x-for="item in selectedItems()" :key="item.id">
                        <tr>
                            <td x-text="item.product_name"></td>
                            <td class="text-right" x-text="formatQty(item.qty_ordered)"></td>
                            <td class="text-right" x-text="formatQty(item.qty_picked_up)"></td>
                            <td class="text-right" x-text="formatQty(item.qty_balance)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="mt-6 flex items-center justify-end"><button class="btn" type="button" @click="closePoDetails()">Close</button></div>
        </div>
    </div>
</div>

<script>
    function voidedPosReport() {
        return {
            itemsBySupplierOrder: <?= $itemsJson ?>,
            rows: <?= $rowsJson ?>,
            poDetailsOpen: false,
            selectedSupplierOrderId: null,
            openPoDetails(id) { this.selectedSupplierOrderId = id; this.poDetailsOpen = true; },
            closePoDetails() { this.poDetailsOpen = false; this.selectedSupplierOrderId = null; },
            selectedPoNumber() {
                const row = this.rows.find((item) => String(item.id) === String(this.selectedSupplierOrderId));
                return row ? row.po_no : '';
            },
            selectedItems() { return this.itemsBySupplierOrder[this.selectedSupplierOrderId] || []; },
            formatQty(value) {
                return (Math.round((parseFloat(value) || 0) * 100) / 100).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
        };
    }
</script>
<?= $this->endSection() ?>
