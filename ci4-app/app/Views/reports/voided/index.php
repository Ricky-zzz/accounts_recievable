<?php

/**
 * @var string $fromVoidedDate
 * @var string $toVoidedDate
 * @var string $drNo
 * @var list<array{id?: int|string, date?: string|null, dr_no?: string|null, client_name?: string|null, due_date?: string|null, total_amount?: int|float|string|null, balance?: int|float|string|null, voided_at?: string|null, void_reason?: string|null}> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByDelivery
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByDelivery
 * @var array<int|string, list<array<string, int|float|string|null>>> $historiesByDelivery
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsByDelivery ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByDelivery ?? [], $jsonFlags);
$historiesJson = json_encode($historiesByDelivery ?? [], $jsonFlags);
?>

<div x-data="voidedReport()">
    <div class="space-y-6">
        <?php
        $filterQuery = [
            'dr_no' => $drNo ?? '',
            'from_voided_date' => $fromVoidedDate ?? '',
            'to_voided_date' => $toVoidedDate ?? '',
        ];
        ?>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Voided Deliveries Report</h1>
                <p class="mt-1 text-sm muted">Latest voided deliveries with reasons and balances.</p>
            </div>
            <a class="btn" href="<?= base_url('reports/voided/print?' . http_build_query($filterQuery)) ?>" target="_blank">Print</a>
        </div>

        <form method="get" action="<?= base_url('reports/voided') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
            <input type="hidden" name="from_voided_date" x-ref="fromDate" value="<?= esc($fromVoidedDate ?? '') ?>">
            <input type="hidden" name="to_voided_date" x-ref="toDate" value="<?= esc($toVoidedDate ?? '') ?>">
            <div class="grid gap-4 md:grid-cols-5">
            <div>
                <label class="block text-sm font-medium" for="dr_no">DR Number</label>
                <input class="input mt-1" id="dr_no" name="dr_no" value="<?= esc($drNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
            </div>
            <div>
                <label class="block text-sm font-medium" for="from_voided_date">From Voided Date</label>
                <input class="input mt-1" id="from_voided_date" x-ref="fromDateDraft" type="date" value="<?= esc($fromVoidedDate ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium" for="to_voided_date">To Voided Date</label>
                <input class="input mt-1" id="to_voided_date" x-ref="toDateDraft" type="date" value="<?= esc($toVoidedDate ?? '') ?>">
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <button class="btn btn-strong" type="submit" @click="$refs.fromDate.value = $refs.fromDateDraft.value; $refs.toDate.value = $refs.toDateDraft.value">Filter</button>
                <a class="btn btn-secondary" href="<?= base_url('reports/voided') ?>">Clear</a>
            </div>
            </div>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>DR #</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Voided At</th>
                    <th>Client</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Balance</th>
                    <th>Reason</th>
                    <th class="text-center">History</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10">No voided deliveries found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php $rowId = (int) ($row['id'] ?? 0); ?>
                        <tr>
                            <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                            <td>
                                <button class="btn-link" type="button" @click="openDrDetails(<?= $rowId ?>)">
                                    <?= esc((string) ($row['dr_no'] ?? '')) ?>
                                </button>
                            </td>
                            <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['due_date'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['voided_at'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['client_name'] ?? '')) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                            <td><?= esc((string) ($row['void_reason'] ?? '')) ?></td>
                            <td class="text-center">
                                <?php if (! empty($historiesByDelivery[$rowId])): ?>
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
                <div>
                    Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (($currentPage ?? 1) > 1): ?>
                        <a class="btn btn-secondary" href="<?= base_url('reports/voided?' . http_build_query($filterQuery + ['page' => $currentPage - 1])) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                        <a class="btn btn-secondary" href="<?= base_url('reports/voided?' . http_build_query($filterQuery + ['page' => $currentPage + 1])) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-backdrop" x-show="historyOpen" x-cloak @click.self="closeHistory()">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="sticky top-0 z-10 -mx-6 -mt-6 border-b border-gray-200 bg-white p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Delivery History</h2>
                        <p class="mt-1 text-sm muted" x-text="selectedHistoryDelivery() ? 'DR# ' + (selectedHistoryDelivery().dr_no || '-') + ' | Current total ' + formatAmount(selectedHistoryDelivery().total_amount || 0) : ''"></p>
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
                                <p class="mt-2">DR#: <span x-text="historyDelivery(history.old_delivery_json).dr_no || '-'" ></span></p>
                                <p>Date: <span x-text="historyDelivery(history.old_delivery_json).date || '-'" ></span></p>
                                <p>Total: <span x-text="formatAmount(historyDelivery(history.old_delivery_json).total_amount || 0)" ></span></p>
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
                                <p class="mt-2">DR#: <span x-text="historyDelivery(history.new_delivery_json).dr_no || '-'" ></span></p>
                                <p>Date: <span x-text="historyDelivery(history.new_delivery_json).date || '-'" ></span></p>
                                <p>Total: <span x-text="formatAmount(historyDelivery(history.new_delivery_json).total_amount || 0)" ></span></p>
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
    <?= view('components/transaction_details/delivery_modal') ?>
</div>

<script>
    function voidedReport() {
        return {
            ...transactionDetailsState({
                endpoints: {
                    delivery: '<?= base_url('ajax/deliveries') ?>',
                },
            }),
            itemsByDelivery: <?= $itemsJson ?>,
            allocationsByDelivery: <?= $allocationsJson ?>,
            historiesByDelivery: <?= $historiesJson ?>,
            drDetailsOpen: false,
            historyOpen: false,
            selectedDeliveryId: null,
            selectedHistoryId: null,
            rows: <?= json_encode($rows ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            normalizeAmount(value) {
                return Math.round((parseFloat(value) || 0) * 100) / 100;
            },
            formatAmount(value) {
                return this.normalizeAmount(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
            openDrDetails(id) {
                const row = this.rows.find((item) => String(item.id) === String(id));
                this.openDetail('delivery', id, row ? (row.dr_no || '') : '');
            },
            closeDrDetails() {
                this.closeDetail('delivery');
            },
            openHistory(id) {
                this.selectedHistoryId = id;
                this.historyOpen = true;
            },
            closeHistory() {
                this.historyOpen = false;
                this.selectedHistoryId = null;
            },
            selectedDrNumber() {
                const row = this.rows.find((r) => String(r.id) === String(this.selectedDeliveryId));
                return row ? row.dr_no : '';
            },
            selectedHistoryDelivery() {
                return this.rows.find((r) => String(r.id) === String(this.selectedHistoryId)) || null;
            },
            selectedItems() {
                return this.itemsByDelivery[this.selectedDeliveryId] || [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            selectedAllocations() {
                return this.allocationsByDelivery[this.selectedDeliveryId] || [];
            },
            allocationsTotal() {
                return this.selectedAllocations()
                    .reduce((sum, alloc) => sum + (parseFloat(alloc.amount) || 0), 0)
                    .toFixed(2);
            },
            selectedHistories() {
                return this.historiesByDelivery[this.selectedHistoryId] || [];
            },
            historyDelivery(value) {
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
            }
        };
    }
</script>

<?= $this->endSection() ?>
