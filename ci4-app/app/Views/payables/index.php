<?php
/**
 * @var string $fromDate
 * @var string $toDate
 * @var string $prNo
 * @var list<array<string, int|float|string|null>> $payables
 * @var array<int|string, array<string, int|float|string|null>> $payablesById
 * @var int|float|string $totalPayments
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$payablesJson = json_encode($payablesById ?? [], $jsonFlags);
$listUrl = base_url('payables');
$printUrl = base_url('payables/print');
$printQuery = http_build_query([
    'from_date' => $fromDate ?? '',
    'to_date' => $toDate ?? '',
    'pr_no' => $prNo ?? '',
]);
?>

<div x-data="payablesList()" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">
                Payments
            </h1>
            <p class="mt-1 text-sm muted">Filter payments by PR number and date range.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a class="btn btn-secondary" href="<?= base_url('supplier-orders') ?>">PO</a>
            <a class="btn btn-secondary" href="<?= base_url('purchase-orders') ?>">Pickup</a>
            <a class="btn btn-secondary" target="_blank" href="<?= esc($printUrl . '?' . $printQuery) ?>">Print</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>">Back</a>
        </div>
    </div>

    <form class="filter-card rounded border border-gray-200 p-4" method="get" action="<?= esc($listUrl) ?>" x-data>
        <div class="grid gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium" for="pr_no">CV Number</label>
            <input class="input mt-1" id="pr_no" name="pr_no" value="<?= esc($prNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="from_date">From Date</label>
            <input class="input mt-1" id="from_date" name="from_date" type="date" value="<?= esc($fromDate ?? '') ?>" @change="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_date">To Date</label>
            <input class="input mt-1" id="to_date" name="to_date" type="date" value="<?= esc($toDate ?? '') ?>" @change="$el.form.requestSubmit()">
        </div>
        <div class="flex items-end gap-2">
            <a class="btn btn-secondary" href="<?= esc($listUrl) ?>">Clear</a>
        </div>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Supplier</th>
                <th>CV #</th>
                <th class="text-right" style="text-align: right;">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payables)): ?>
                <tr><td colspan="4">No payables found.</td></tr>
            <?php else: ?>
                <?php foreach ($payables as $payable): ?>
                    <tr>
                        <td><?= esc((string) ($payable['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($payable['supplier_name'] ?? '')) ?></td>
                        <td>
                            <button class="btn-link" type="button" @click="openPayable(<?= (int) $payable['id'] ?>)">
                                <?= esc((string) ($payable['pr_no'] ?? '')) ?>
                            </button>
                        </td>
                        <td class="text-right"><?= esc(number_format((float) ($payable['amount_received'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="card p-4 total-highlight">
        <div class="flex justify-between">
            <span>Total Payments</span>
            <span><?= esc(number_format((float) $totalPayments, 2)) ?></span>
        </div>
    </div>

    <div class="modal-backdrop" x-show="modalOpen" x-cloak @click.self="closePayable()">
        <div class="modal-panel max-w-3xl p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold">CV Details <span x-text="selectedPayable() ? selectedPayable().pr_no : ''"></span></h2>
                <button class="btn btn-secondary" type="button" @click="closePayable()">Close</button>
            </div>
            <div class="mt-4 text-sm muted" x-show="detailLoading">Loading details...</div>
            <div class="mt-4 text-sm text-red-600" x-show="detailError" x-text="detailError"></div>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div class="card p-3"><p class="muted">Amount Paid</p><p class="font-semibold" x-text="selectedPayable() ? Number(selectedPayable().amount_received || 0).toFixed(2) : '0.00'"></p></div>
                <div class="card p-3"><p class="muted">Allocated to POs</p><p class="font-semibold" x-text="allocatedTotal().toFixed(2)"></p></div>
                <div class="card p-3"><p class="muted">Other Accounts</p><p class="font-semibold" x-text="otherAccountsTotal().toFixed(2)"></p></div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold">PO Allocations</h3>
                    <table class="table mt-3">
                        <thead><tr><th>RR #</th><th>Date</th><th>Amount</th></tr></thead>
                        <tbody>
                            <template x-if="detailLoading"><tr><td colspan="3">Loading...</td></tr></template>
                            <template x-if="!detailLoading && selectedAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template>
                            <template x-for="(allocation, index) in selectedAllocations()" :key="index"><tr><td x-text="allocation.po_no"></td><td x-text="allocation.date"></td><td x-text="Number(allocation.amount).toFixed(2)"></td></tr></template>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="text-sm font-semibold">Other Accounts</h3>
                    <table class="table mt-3">
                        <thead><tr><th>Account Title</th><th>Amount</th></tr></thead>
                        <tbody>
                            <template x-if="detailLoading"><tr><td colspan="2">Loading...</td></tr></template>
                            <template x-if="!detailLoading && selectedOtherAccounts().length === 0"><tr><td colspan="2">No other accounts found.</td></tr></template>
                            <template x-for="(item, index) in selectedOtherAccounts()" :key="index"><tr><td x-text="item.account_title"></td><td x-text="Number(item.other_accounts || 0).toFixed(2)"></td></tr></template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function payablesList() {
        return {
            payablesById: <?= $payablesJson ?>,
            detailUrl: '<?= base_url('ajax/payables') ?>',
            detailsByPayable: {},
            modalOpen: false,
            selectedPayableId: null,
            detailLoading: false,
            detailError: '',
            async openPayable(id) {
                this.selectedPayableId = id;
                this.modalOpen = true;
                this.detailError = '';
                await this.loadPayableDetails(id);
            },
            closePayable() { this.modalOpen = false; this.selectedPayableId = null; },
            selectedPayableDetail() { return this.detailsByPayable[this.selectedPayableId] || null; },
            selectedPayable() {
                const detail = this.selectedPayableDetail();
                return detail ? detail.payable : (this.payablesById[this.selectedPayableId] || null);
            },
            selectedAllocations() {
                const detail = this.selectedPayableDetail();
                return detail ? (detail.allocations || []) : [];
            },
            selectedOtherAccounts() {
                const detail = this.selectedPayableDetail();
                return detail ? (detail.other_accounts || []) : [];
            },
            allocatedTotal() { return this.selectedAllocations().reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0); },
            otherAccountsTotal() { return this.selectedOtherAccounts().reduce((sum, item) => sum + (parseFloat(item.other_accounts) || 0), 0); },
            async loadPayableDetails(id) {
                if (!id || this.detailsByPayable[id]) {
                    return;
                }

                this.detailLoading = true;
                this.detailError = '';
                try {
                    const response = await fetch(this.detailUrl + '/' + id, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Unable to load CV details.');
                    }
                    this.detailsByPayable[id] = data;
                } catch (error) {
                    this.detailError = error.message || 'Unable to load CV details.';
                } finally {
                    this.detailLoading = false;
                }
            },
        };
    }
</script>
<?= $this->endSection() ?>
