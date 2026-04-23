<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$allocationsJson = json_encode($allocationsByPayment ?? [], $jsonFlags);
?>

<div x-data="paymentList()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">Payments for <?= esc($client['name'] ?? '') ?></h2>
            <p class="mt-1 text-sm muted">Filter payments by date range.</p>
        </div>

        <div class="flex items-center gap-2">
            <?php if (! empty($client['id'])): ?>
                <a class="btn" href="<?= base_url('payments/client/' . $client['id'] . '/create') ?>">Pay</a>
                <a class="btn btn-secondary" target="_blank" href="<?= base_url('payments/client/' . $client['id'] . '/print') ?>?from_date=<?= esc($fromDate ?? '') ?>&to_date=<?= esc($toDate ?? '') ?>">Print PDF</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('clients') ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= base_url('payments/client/' . ($client['id'] ?? 0)) ?>" class="mt-4 grid gap-4 sm:grid-cols-3">
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
            <a class="btn btn-secondary" href="<?= base_url('payments/client/' . ($client['id'] ?? 0)) ?>">Clear</a>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>Date</th>
                <th>PR #</th>
                <th>Collections</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td class="py-3" colspan="3">No payments found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= esc($payment['date']) ?></td>
                        <td>
                            <?php if (! empty($allocationsByPayment[$payment['id']])): ?>
                                <button class="btn-link" type="button" @click="openAllocations(<?= (int) $payment['id'] ?>)">
                                    <?= esc($payment['pr_no'] ?? '') ?>
                                </button>
                            <?php else: ?>
                                <?= esc($payment['pr_no'] ?? '') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc(number_format((float) $payment['amount_received'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="card p-4 text-sm">
            <div class="flex justify-between">
                <span>Total Collections</span>
                <span><?= esc(number_format((float) $totalCollections, 2)) ?></span>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak>
        <div class="modal-panel max-w-lg p-6">
            <h2 class="text-lg font-semibold">Payment Allocations</h2>
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>DR #</th>
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
                            <td x-text="allocation.dr_no"></td>
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
    function paymentList() {
        return {
            allocationsByPayment: <?= $allocationsJson ?>,
            allocOpen: false,
            selectedPaymentId: null,
            openAllocations(id) {
                this.selectedPaymentId = id;
                this.allocOpen = true;
            },
            closeAllocations() {
                this.allocOpen = false;
                this.selectedPaymentId = null;
            },
            selectedAllocations() {
                return this.allocationsByPayment[this.selectedPaymentId] || [];
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