<?php
/**
 * @var string $fromDate
 * @var string $toDate
 * @var string $prNo
 * @var list<array{id: int|string, client_id?: int|string|null, pr_no?: int|string|null, date?: string|null, client_name?: string|null, amount_received?: int|float|string|null}> $payments
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPayment
 * @var array<int|string, list<array<string, int|float|string|null>>> $otherAccountsByPayment
 * @var array<int|string, array<string, int|float|string|null>> $paymentsById
 * @var int|float|string $totalCollections
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$allocationsJson = json_encode($allocationsByPayment ?? [], $jsonFlags);
$paymentOtherJson = json_encode($otherAccountsByPayment ?? [], $jsonFlags);
$paymentsByIdJson = json_encode($paymentsById ?? [], $jsonFlags);
$paymentsJson = json_encode($payments ?? [], $jsonFlags);
?>

<div x-data="paymentList()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Payments</h1>
            <p class="mt-1 text-sm muted">Filter payments by date range.</p>
        </div>
        <a class="btn btn-secondary" target="_blank" href="<?= base_url('payments/print') ?>?from_date=<?= esc($fromDate ?? '') ?>&to_date=<?= esc($toDate ?? '') ?>&pr_no=<?= esc($prNo ?? '') ?>">Print</a>
    </div>

    <form method="get" action="<?= base_url('payments') ?>" class="filter-card mt-4 rounded border border-gray-200 p-4" x-data>
        <div class="grid gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium" for="pr_no">PR Number</label>
            <input
                class="input mt-1"
                id="pr_no"
                name="pr_no"
                value="<?= esc($prNo ?? '') ?>"
                @input.debounce.1000ms="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="from_date">From Date</label>
            <input
                class="input mt-1"
                id="from_date"
                name="from_date"
                type="date"
                value="<?= esc($fromDate ?? '') ?>"
                @change="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_date">To Date</label>
            <input
                class="input mt-1"
                id="to_date"
                name="to_date"
                type="date"
                value="<?= esc($toDate ?? '') ?>"
                @change="$el.form.requestSubmit()">
        </div>
        <div class="flex items-end gap-2">
            <a class="btn btn-secondary" href="<?= base_url('payments') ?>">Clear</a>
        </div>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Client</th>
                <th>PR #</th>
                <th>Collections</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td class="py-3" colspan="6">No payments found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $index => $payment): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $payment['date']) ?></td>
                        <td><?= esc((string) ($payment['client_name'] ?? '')) ?></td>
                        <td>
                            <?php if (! empty($allocationsByPayment[$payment['id']])): ?>
                                <button class="btn-link" type="button" @click="openAllocations(<?= (int) $payment['id'] ?>)">
                                    <?= esc((string) ($payment['pr_no'] ?? '')) ?>
                                </button>
                            <?php else: ?>
                                <?= esc((string) ($payment['pr_no'] ?? '')) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc(number_format((float) $payment['amount_received'], 2)) ?></td>
                        <td class="text-center">
                            <button class="btn btn-secondary" type="button" @click="openSoaModal(<?= (int) ($payment['client_id'] ?? 0) ?>, '<?= esc((string) ($payment['client_name'] ?? ''), 'js') ?>', '<?= esc((string) ($payment['client_payment_term'] ?? ''), 'js') ?>')" <?= empty($payment['client_id']) ? 'disabled' : '' ?>>SOA</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="card p-4 total-highlight">
            <div class="flex justify-between">
                <span>Total Collections</span>
                <span><?= esc(number_format((float) $totalCollections, 2)) ?></span>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak @click.self="closeAllocations()">
        <div class="modal-panel max-w-lg p-6" @click.stop>
            <h2 class="text-lg font-semibold">PR Summary for: <span x-text="selectedPrNumber()"></span></h2>
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
            <div class="mt-6 space-y-5">
                <div class="rounded border border-gray-200 p-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold">Original Amount Received</span>
                        <span x-text="selectedPayment() ? Number(selectedPayment().amount_received || 0).toFixed(2) : '0.00'"></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="font-semibold">Allocated to DRs</span>
                        <span x-text="selectedAllocatedTotal().toFixed(2)"></span>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Other Accounts</h3>
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>Account Title</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="selectedOtherAccountsBreakdown().length === 0">
                                <tr>
                                    <td class="py-3" colspan="2">No other accounts found.</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in selectedOtherAccountsBreakdown()" :key="'other-' + index">
                                <tr>
                                    <td x-text="item.account_title"></td>
                                    <td x-text="Number(item.dr || item.ar_others || 0).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="mt-3 rounded border border-gray-200 p-4 text-sm" x-show="selectedArOther()" x-cloak>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">A/R Other Description</span>
                            <span x-text="selectedArOther() ? (selectedArOther().description || '-') : '-' "></span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="font-semibold">A/R Other Amount</span>
                            <span x-text="selectedArOther() ? Number(selectedArOther().ar_others || 0).toFixed(2) : '0.00'"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn" type="button" @click="closeAllocations()">Close</button>
            </div>
        </div>
    </div>
    <?= view('clients/_soa_modal') ?>
</div>

<script>
    function paymentList() {
        return {
            ...soaModalState(),
            allocationsByPayment: <?= $allocationsJson ?>,
            otherAccountsByPayment: <?= $paymentOtherJson ?>,
            paymentsById: <?= $paymentsByIdJson ?>,
            payments: <?= $paymentsJson ?>,
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
            selectedPayment() {
                return this.paymentsById[this.selectedPaymentId] || null;
            },
            selectedAllocatedTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
            },
            selectedOtherAccounts() {
                return this.otherAccountsByPayment[this.selectedPaymentId] || [];
            },
            selectedOtherAccountsBreakdown() {
                return this.selectedOtherAccounts().filter((item) => (parseFloat(item.dr) || 0) > 0 && (item.account_title || '').trim() !== '');
            },
            selectedArOther() {
                return this.selectedOtherAccounts().find((item) => (parseFloat(item.ar_others) || 0) > 0) || null;
            },
            allocTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
                    .toFixed(2);
            },
            selectedPrNumber() {
                const payment = this.paymentsById[String(this.selectedPaymentId)];
                return payment ? payment.pr_no : '';
            }
        };
    }
</script>
<?= $this->endSection() ?>
