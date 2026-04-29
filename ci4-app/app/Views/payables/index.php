<?php
/**
 * @var array{id?: int|string, name?: string|null}|null $supplier
 * @var string $fromDate
 * @var string $toDate
 * @var string $prNo
 * @var list<array<string, int|float|string|null>> $payables
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPayable
 * @var array<int|string, list<array<string, int|float|string|null>>> $otherAccountsByPayable
 * @var array<int|string, array<string, int|float|string|null>> $payablesById
 * @var int|float|string $totalPayments
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$payablesJson = json_encode($payablesById ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByPayable ?? [], $jsonFlags);
$otherJson = json_encode($otherAccountsByPayable ?? [], $jsonFlags);
$listUrl = $supplier ? base_url('payables/supplier/' . $supplier['id']) : base_url('payables');
$printUrl = $supplier ? base_url('payables/supplier/' . $supplier['id'] . '/print') : base_url('payables/print');
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
                <?= $supplier ? 'Payables for ' . esc((string) ($supplier['name'] ?? '')) : 'Payables' ?>
            </h1>
            <p class="mt-1 text-sm muted">Filter payables by PR number and date range.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($supplier): ?>
                <a class="btn" href="<?= base_url('payables/supplier/' . $supplier['id'] . '/create') ?>">Pay Supplier</a>
                <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplier['id'] . '/purchase-orders') ?>">Orders</a>
                <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . $supplier['id']) ?>">Ledger</a>
            <?php endif; ?>
            <a class="btn btn-secondary" target="_blank" href="<?= esc($printUrl . '?' . $printQuery) ?>">Print PDF</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>">Back</a>
        </div>
    </div>

    <form class="grid gap-4 md:grid-cols-4" method="get" action="<?= esc($listUrl) ?>">
        <div>
            <label class="block text-sm font-medium" for="pr_no">PR Number</label>
            <input class="input mt-1" id="pr_no" name="pr_no" value="<?= esc($prNo ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="from_date">From Date</label>
            <input class="input mt-1" id="from_date" name="from_date" type="date" value="<?= esc($fromDate ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_date">To Date</label>
            <input class="input mt-1" id="to_date" name="to_date" type="date" value="<?= esc($toDate ?? '') ?>">
        </div>
        <div class="flex items-end gap-2">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?= esc($listUrl) ?>">Clear</a>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <?php if (! $supplier): ?><th>Supplier</th><?php endif; ?>
                <th>PR #</th>
                <th class="text-right" style="text-align: right;">Amount Paid</th>
                <th class="text-right" style="text-align: right;">Allocated</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payables)): ?>
                <tr><td colspan="<?= $supplier ? '4' : '5' ?>">No payables found.</td></tr>
            <?php else: ?>
                <?php foreach ($payables as $payable): ?>
                    <tr>
                        <td><?= esc((string) ($payable['date'] ?? '')) ?></td>
                        <?php if (! $supplier): ?><td><?= esc((string) ($payable['supplier_name'] ?? '')) ?></td><?php endif; ?>
                        <td>
                            <button class="btn-link" type="button" @click="openPayable(<?= (int) $payable['id'] ?>)">
                                <?= esc((string) ($payable['pr_no'] ?? '')) ?>
                            </button>
                        </td>
                        <td class="text-right"><?= esc(number_format((float) ($payable['amount_received'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($payable['amount_allocated'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="card p-4 text-sm">
        <div class="flex justify-between">
            <span>Total Payments</span>
            <span><?= esc(number_format((float) $totalPayments, 2)) ?></span>
        </div>
    </div>

    <div class="modal-backdrop" x-show="modalOpen" x-cloak @click.self="closePayable()">
        <div class="modal-panel max-w-3xl p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold">PR Details <span x-text="selectedPayable() ? selectedPayable().pr_no : ''"></span></h2>
                <button class="btn btn-secondary" type="button" @click="closePayable()">Close</button>
            </div>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div class="card p-3"><p class="muted">Amount Paid</p><p class="font-semibold" x-text="selectedPayable() ? Number(selectedPayable().amount_received || 0).toFixed(2) : '0.00'"></p></div>
                <div class="card p-3"><p class="muted">Allocated to POs</p><p class="font-semibold" x-text="allocatedTotal().toFixed(2)"></p></div>
                <div class="card p-3"><p class="muted">Other Accounts</p><p class="font-semibold" x-text="otherAccountsTotal().toFixed(2)"></p></div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold">PO Allocations</h3>
                    <table class="table mt-3">
                        <thead><tr><th>PO #</th><th>Date</th><th>Amount</th></tr></thead>
                        <tbody>
                            <template x-if="selectedAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template>
                            <template x-for="(allocation, index) in selectedAllocations()" :key="index"><tr><td x-text="allocation.po_no"></td><td x-text="allocation.date"></td><td x-text="Number(allocation.amount).toFixed(2)"></td></tr></template>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="text-sm font-semibold">Other Accounts</h3>
                    <table class="table mt-3">
                        <thead><tr><th>Account Title</th><th>Amount</th></tr></thead>
                        <tbody>
                            <template x-if="selectedOtherAccounts().length === 0"><tr><td colspan="2">No other accounts found.</td></tr></template>
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
            allocationsByPayable: <?= $allocationsJson ?>,
            otherAccountsByPayable: <?= $otherJson ?>,
            modalOpen: false,
            selectedPayableId: null,
            openPayable(id) { this.selectedPayableId = id; this.modalOpen = true; },
            closePayable() { this.modalOpen = false; this.selectedPayableId = null; },
            selectedPayable() { return this.payablesById[this.selectedPayableId] || null; },
            selectedAllocations() { return this.allocationsByPayable[this.selectedPayableId] || []; },
            selectedOtherAccounts() { return this.otherAccountsByPayable[this.selectedPayableId] || []; },
            allocatedTotal() { return this.selectedAllocations().reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0); },
            otherAccountsTotal() { return this.selectedOtherAccounts().reduce((sum, item) => sum + (parseFloat(item.other_accounts) || 0), 0); },
        };
    }
</script>
<?= $this->endSection() ?>
