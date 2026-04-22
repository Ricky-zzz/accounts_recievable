<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$itemsJson = json_encode($itemsByDelivery ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$deliveryAllocJson = json_encode($allocationsByDelivery ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$paymentAllocJson = json_encode($allocationsByPayment ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<script>
    // Store data in window before Alpine initializes
    window.ledgerData = {
        itemsByDelivery: <?= $itemsJson ?>,
        allocationsByDelivery: <?= $deliveryAllocJson ?>,
        allocationsByPayment: <?= $paymentAllocJson ?>
    };
    
    function ledgerItems() {
        console.log('Ledger data:', window.ledgerData);
        return {
            itemsByDelivery: window.ledgerData.itemsByDelivery,
            allocationsByDelivery: window.ledgerData.allocationsByDelivery,
            allocationsByPayment: window.ledgerData.allocationsByPayment,
            itemsOpen: false,
            allocOpen: false,
            allocType: '',
            selectedItemId: null,
            selectedAllocId: null,
            openItems(id) {
                this.selectedItemId = id;
                this.itemsOpen = true;
                this.allocOpen = false;
            },
            closeItems() {
                this.itemsOpen = false;
                this.selectedItemId = null;
            },
            selectedItems() {
                return this.itemsByDelivery[this.selectedItemId] || [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            openDeliveryAllocations(id) {
                this.allocType = 'delivery';
                this.selectedAllocId = id;
                this.allocOpen = true;
                this.itemsOpen = false;
            },
            openPaymentAllocations(id) {
                this.allocType = 'payment';
                this.selectedAllocId = id;
                this.allocOpen = true;
                this.itemsOpen = false;
            },
            closeAllocations() {
                this.allocOpen = false;
                this.allocType = '';
                this.selectedAllocId = null;
            },
            selectedAllocations() {
                if (this.allocType === 'delivery') {
                    return this.allocationsByDelivery[this.selectedAllocId] || [];
                }
                if (this.allocType === 'payment') {
                    return this.allocationsByPayment[this.selectedAllocId] || [];
                }
                return [];
            },
            allocTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
                    .toFixed(2);
            },
            allocTitle() {
                return this.allocType === 'delivery' ? 'DR Allocations' : 'PR Allocations';
            }
        };
    }
</script>

<div x-data="ledgerItems()">
    <h1 class="text-xl font-semibold">Client Ledger</h1>
    <p class="mt-1 text-sm muted">Shows overall balance with optional date range.</p>

    <form class="mt-6 grid gap-4 sm:grid-cols-4" method="get" action="<?= base_url('ledger') ?>">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium" for="client_name">Client</label>
            <input class="input mt-1" id="client_name" value="<?= esc($selectedClient['name'] ?? 'Select a client from the Clients list') ?>" readonly>
            <input type="hidden" name="client_id" value="<?= esc($clientId) ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="start">Start Date</label>
            <input class="input mt-1" id="start" name="start" type="date" value="<?= esc($start) ?: date('Y-m-d') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="end">End Date</label>
            <input class="input mt-1" id="end" name="end" type="date" value="<?= esc($end) ?: date('Y-m-d') ?>">
        </div>
        <div class="sm:col-span-4">
            <button class="btn" type="submit">Filter</button>
        </div>
    </form>

    <?php if ($selectedClient): ?>
        <!-- <div class="mt-6 card p-4 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-semibold">Import Ledger</div>
                    <div class="mt-1 text-xs muted">CSV or Excel import (coming soon).</div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input class="input" type="file" accept=".csv,.xlsx,.xls" disabled>
                    <button class="btn btn-secondary" type="button" disabled>Import</button>
                </div>
            </div>
        </div> -->

        <div class="mt-6 card p-4 text-sm">
            <div class="flex justify-between">
                <span>Last Balance</span>
                <span><?= esc(number_format((float) $openingBalance, 2)) ?></span>
            </div>
        </div>

        <table class="table mt-6">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>DR#</th>
                    <th>PR#</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Amount</th>
                    <th>Collection</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td class="py-3" colspan="8">No deliveries in range.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= esc($row['entry_date']) ?></td>
                            <td>
                                <?php if (! empty($row['delivery_id']) && ! empty($allocationsByDelivery[$row['delivery_id']])): ?>
                                    <button class="btn-link" type="button" @click="openDeliveryAllocations(<?= (int) $row['delivery_id'] ?>)">
                                        <?= esc($row['dr_no'] ?? '') ?>
                                    </button>
                                <?php else: ?>
                                    <?= esc($row['dr_no'] ?? '') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (! empty($row['payment_id']) && ! empty($allocationsByPayment[$row['payment_id']])): ?>
                                    <button class="btn-link" type="button" @click="openPaymentAllocations(<?= (int) $row['payment_id'] ?>)">
                                        <?= esc($row['pr_no'] ?? '') ?>
                                    </button>
                                <?php else: ?>
                                    <?= esc($row['pr_no'] ?? '') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= esc($row['qty'] ?? '') ?>
                                <?php if (! empty($row['delivery_id']) && ($itemCounts[$row['delivery_id']] ?? 0) > 1): ?>
                                    <span class="muted">+</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($row['price'] ?? '') ?></td>
                            <td>
                                <?php if (! empty($row['delivery_id']) && ! empty($itemsByDelivery[$row['delivery_id']])): ?>
                                    <button class="btn-link" type="button" @click="openItems(<?= (int) $row['delivery_id'] ?>)">
                                        <?= esc(number_format((float) $row['amount'], 2)) ?>
                                    </button>
                                <?php else: ?>
                                    <?= esc(number_format((float) $row['amount'], 2)) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $collection = (float) ($row['collection'] ?? 0); ?>
                                <?= $collection > 0 ? esc(number_format($collection, 2)) : '' ?>
                            </td>
                            <td><?= esc(number_format((float) $row['balance'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

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
            <h2 class="text-lg font-semibold" x-text="allocTitle()"></h2>
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th x-text="allocType === 'delivery' ? 'PR#' : 'DR#'"></th>
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
                            <td x-text="allocType === 'delivery' ? allocation.pr_no : allocation.dr_no"></td>
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

<?= $this->endSection() ?>