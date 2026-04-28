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
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsByDelivery ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByDelivery ?? [], $jsonFlags);
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

        <form method="get" action="<?= base_url('reports/voided') ?>" class="grid gap-4 md:grid-cols-5">
            <div>
                <label class="block text-sm font-medium" for="dr_no">DR Number</label>
                <input class="input mt-1" id="dr_no" name="dr_no" value="<?= esc($drNo ?? '') ?>">
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
                <a class="btn btn-secondary" href="<?= base_url('reports/voided') ?>">Clear</a>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9">No voided deliveries found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                            <td>
                                <button class="btn-link" type="button" @click="openDrDetails(<?= (int) ($row['id'] ?? 0) ?>)">
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

    <div class="modal-backdrop" x-show="drDetailsOpen" x-cloak @click.self="closeDrDetails()">
        <div class="modal-panel max-w-4xl p-6" @click.stop>
            <div class="mb-4 border-b pb-4">
                <h2 class="text-lg font-semibold">Details for DR#: <span x-text="selectedDrNumber()"></span></h2>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="mb-3 font-semibold">Delivery Items</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="selectedItems().length === 0">
                                <tr>
                                    <td class="py-3 text-center" colspan="4">No items found.</td>
                                </tr>
                            </template>
                            <template x-for="item in selectedItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name"></td>
                                    <td x-text="item.qty"></td>
                                    <td x-text="Number(item.unit_price).toFixed(2)"></td>
                                    <td x-text="Number(item.line_total).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="mt-2 text-sm font-semibold" x-show="selectedItems().length > 0">
                        Total: <span x-text="itemsTotal()"></span>
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold">DR Allocations</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>PR #</th>
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
                    <div class="mt-2 text-sm font-semibold" x-show="selectedAllocations().length > 0">
                        Total: <span x-text="allocationsTotal()"></span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button class="btn" type="button" @click="closeDrDetails()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function voidedReport() {
        return {
            itemsByDelivery: <?= $itemsJson ?>,
            allocationsByDelivery: <?= $allocationsJson ?>,
            drDetailsOpen: false,
            selectedDeliveryId: null,
            rows: <?= json_encode($rows ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            openDrDetails(id) {
                this.selectedDeliveryId = id;
                this.drDetailsOpen = true;
            },
            closeDrDetails() {
                this.drDetailsOpen = false;
                this.selectedDeliveryId = null;
            },
            selectedDrNumber() {
                const row = this.rows.find((r) => String(r.id) === String(this.selectedDeliveryId));
                return row ? row.dr_no : '';
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
            }
        };
    }
</script>

<?= $this->endSection() ?>