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
        <input type="hidden" name="from_date" x-ref="fromDate" value="<?= esc($fromDate ?? '') ?>">
        <input type="hidden" name="to_date" x-ref="toDate" value="<?= esc($toDate ?? '') ?>">
        <div class="grid gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium" for="pr_no">CV Number</label>
            <input class="input mt-1" id="pr_no" name="pr_no" value="<?= esc($prNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="from_date">From Date</label>
            <input class="input mt-1" id="from_date" x-ref="fromDateDraft" type="date" value="<?= esc($fromDate ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_date">To Date</label>
            <input class="input mt-1" id="to_date" x-ref="toDateDraft" type="date" value="<?= esc($toDate ?? '') ?>">
        </div>
        <div class="flex items-end gap-2">
            <button class="btn btn-strong" type="submit" @click="$refs.fromDate.value = $refs.fromDateDraft.value; $refs.toDate.value = $refs.toDateDraft.value">Filter</button>
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

    <?= view('components/transaction_details/payable_modal') ?>
</div>

<script>
    function payablesList() {
        return {
            ...transactionDetailsState({
                endpoints: {
                    payable: '<?= base_url('ajax/payables') ?>',
                },
            }),
            payablesById: <?= $payablesJson ?>,
            detailUrl: '<?= base_url('ajax/payables') ?>',
            detailsByPayable: {},
            modalOpen: false,
            selectedPayableId: null,
            detailLoading: false,
            detailError: '',
            async openPayable(id) {
                await this.openDetail('payable', id, this.payablesById[id] ? (this.payablesById[id].pr_no || '') : '');
            },
            closePayable() { this.closeDetail('payable'); },
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
