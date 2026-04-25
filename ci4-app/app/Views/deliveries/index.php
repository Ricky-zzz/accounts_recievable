<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsByDelivery ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByDelivery ?? [], $jsonFlags);
?>

<div x-data="deliveryList()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">Deliveries</h2>
            <p class="mt-1 text-sm muted">Filter deliveries by date range.</p>
        </div>
        <a class="btn btn-secondary" target="_blank" href="<?= base_url('deliveries/print') ?>?from_date=<?= esc($fromDate ?? '') ?>&to_date=<?= esc($toDate ?? '') ?>">Print PDF</a>
    </div>

    <form method="get" action="<?= base_url('deliveries') ?>" class="mt-4 grid gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium" for="from_date">From Date</label>
            <input
                class="input mt-1"
                id="from_date"
                name="from_date"
                type="date"
                value="<?= esc($fromDate ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_date">To Date</label>
            <input
                class="input mt-1"
                id="to_date"
                name="to_date"
                type="date"
                value="<?= esc($toDate ?? '') ?>">
        </div>
        <div class="flex items-end gap-2">
            <button class="btn btn-secondary" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?= base_url('deliveries') ?>">Clear</a>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>Date</th>
                <th>Due Date</th>
                <th>Term</th>
                <th>DR #</th>
                <th>Client</th>
                <th>Total Amount</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr>
                    <td class="py-3" colspan="7">No deliveries found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($deliveries as $delivery): ?>
                    <tr>
                        <td><?= esc($delivery['date']) ?></td>
                        <td><?= esc($delivery['due_date'] ?? '') ?></td>
                        <td><?= esc(($delivery['payment_term'] ?? '') !== '' ? $delivery['payment_term'] . ' days' : '') ?></td>
                        <td>
                            <?php if (! empty($allocationsByDelivery[$delivery['id']])): ?>
                                <button class="btn-link" type="button" @click="openAllocations(<?= (int) $delivery['id'] ?>)">
                                    <?= esc($delivery['dr_no'] ?? '') ?>
                                </button>
                            <?php else: ?>
                                <?= esc($delivery['dr_no'] ?? '') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($delivery['client_name'] ?? '') ?></td>
                        <td>
                            <?php if (! empty($itemsByDelivery[$delivery['id']])): ?>
                                <button class="btn-link" type="button" @click="openItems(<?= (int) $delivery['id'] ?>)">
                                    <?= esc(number_format((float) $delivery['total_amount'], 2)) ?>
                                </button>
                            <?php else: ?>
                                <?= esc(number_format((float) $delivery['total_amount'], 2)) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc(number_format((float) $delivery['balance'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="card p-4 text-sm">
            <div class="flex justify-between">
                <span>Total Amount</span>
                <span><?= esc(number_format((float) $totalAmount, 2)) ?></span>
            </div>
        </div>
        <div class="card p-4 text-sm">
            <div class="flex justify-between">
                <span>Total Balance</span>
                <span><?= esc(number_format((float) $totalBalance, 2)) ?></span>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="itemsOpen" x-cloak>
        <div class="modal-panel max-w-lg p-6">
            <h2 class="text-lg font-semibold">Delivery Items</h2>
            <table class="table mt-4">
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
                            <td class="py-3" colspan="4">No items found.</td>
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
            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="font-semibold">Total</span>
                <span x-text="itemsTotal()"></span>
            </div>
            <div class="mt-4">
                <button class="btn" type="button" @click="closeItems()">Close</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak>
        <div class="modal-panel max-w-lg p-6">
            <h2 class="text-lg font-semibold">DR Allocations</h2>
            <table class="table mt-4">
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
                            <td class="py-3" colspan="3">No allocations found.</td>
                        </tr>
                    </template>
                    <template x-for="(allocation, index) in selectedAllocations()" :key="index">
                        <tr>
                            <td x-text="allocation.pr_no"></td>
                            <td x-text="allocation.date"></td>
                            <td x-text="Number(allocation.amount).toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="font-semibold">Total</span>
                <span x-text="allocTotal()"></span>
            </div>
            <div class="mt-4">
                <button class="btn" type="button" @click="closeAllocations()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function deliveryList() {
        return {
            itemsByDelivery: <?= $itemsJson ?>,
            allocationsByDelivery: <?= $allocationsJson ?>,
            itemsOpen: false,
            allocOpen: false,
            selectedDeliveryId: null,
            openItems(id) {
                this.selectedDeliveryId = id;
                this.itemsOpen = true;
                this.allocOpen = false;
            },
            closeItems() {
                this.itemsOpen = false;
                this.selectedDeliveryId = null;
            },
            selectedItems() {
                return this.itemsByDelivery[this.selectedDeliveryId] || [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            openAllocations(id) {
                this.selectedDeliveryId = id;
                this.allocOpen = true;
                this.itemsOpen = false;
            },
            closeAllocations() {
                this.allocOpen = false;
                this.selectedDeliveryId = null;
            },
            selectedAllocations() {
                return this.allocationsByDelivery[this.selectedDeliveryId] || [];
            },
            allocTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
                    .toFixed(2);
            }
        };
    }
</script>
<?= $this->endSection() ?>
