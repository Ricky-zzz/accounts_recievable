<?php

/**
 * @var array{id: int|string, name: string}|null $selectedSupplier
 * @var int $supplierId
 * @var string $start
 * @var string $end
 * @var int|float|string $openingBalance
 * @var int|float|string $currentBalance
 * @var list<array<string, int|float|string|null>> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $totalPages
 * @var int $rowOffset
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPayable
 * @var array<int|string, list<array<string, int|float|string|null>>> $otherAccountsByPayable
 * @var array<int|string, array<string, int|float|string|null>> $payablesById
 * @var float $forwardedBalance
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$itemsJson = json_encode($itemsByPurchaseOrder ?? [], $jsonFlags);
$poAllocJson = json_encode($allocationsByPurchaseOrder ?? [], $jsonFlags);
$payableAllocJson = json_encode($allocationsByPayable ?? [], $jsonFlags);
$payableOtherJson = json_encode($otherAccountsByPayable ?? [], $jsonFlags);
$payablesByIdJson = json_encode($payablesById ?? [], $jsonFlags);
$rowsJson = json_encode($rows ?? [], $jsonFlags);
?>
<script>
    window.payableLedgerData = {
        rows: <?= $rowsJson ?>,
        itemsByPurchaseOrder: <?= $itemsJson ?>,
        allocationsByPurchaseOrder: <?= $poAllocJson ?>,
        allocationsByPayable: <?= $payableAllocJson ?>,
        otherAccountsByPayable: <?= $payableOtherJson ?>,
        payablesById: <?= $payablesByIdJson ?>
    };

    function payableLedger() {
        return {
            ...supplierStatementModalState(),
            forwardedBalance: <?= json_encode((float) ($forwardedBalance ?? 0), $jsonFlags) ?>,
            forwardBalanceOpen: false,
            forwardBalanceAmount: <?= json_encode((float) ($forwardedBalance ?? 0), $jsonFlags) ?>,
            rows: window.payableLedgerData.rows,
            itemsByPurchaseOrder: window.payableLedgerData.itemsByPurchaseOrder,
            allocationsByPurchaseOrder: window.payableLedgerData.allocationsByPurchaseOrder,
            allocationsByPayable: window.payableLedgerData.allocationsByPayable,
            otherAccountsByPayable: window.payableLedgerData.otherAccountsByPayable,
            payablesById: window.payableLedgerData.payablesById,
            poDetailsOpen: false,
            allocOpen: false,
            allocType: '',
            selectedPoId: null,
            selectedPayableId: null,
            openForwardBalance() {
                this.forwardBalanceAmount = this.forwardedBalance;
                this.forwardBalanceOpen = true;
            },
            closeForwardBalance() {
                this.forwardBalanceOpen = false;
            },
            openPoDetails(id) {
                this.selectedPoId = id;
                this.poDetailsOpen = true;
                this.allocOpen = false;
            },
            closePoDetails() {
                this.poDetailsOpen = false;
                this.selectedPoId = null;
            },
            openPayableAllocations(id) {
                this.allocType = 'payable';
                this.selectedPayableId = id;
                this.allocOpen = true;
                this.poDetailsOpen = false;
            },
            closeAllocations() {
                this.allocOpen = false;
                this.allocType = '';
                this.selectedPayableId = null;
            },
            selectedItems() {
                return this.itemsByPurchaseOrder[this.selectedPoId] || [];
            },
            selectedPoAllocations() {
                return this.allocationsByPurchaseOrder[this.selectedPoId] || [];
            },
            selectedPayable() {
                return this.payablesById[this.selectedPayableId] || null;
            },
            selectedAllocations() {
                return this.allocationsByPayable[this.selectedPayableId] || [];
            },
            selectedOtherAccounts() {
                return this.otherAccountsByPayable[this.selectedPayableId] || [];
            },
            selectedAllocatedTotal() {
                return this.selectedAllocations().reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
            },
            selectedOtherAccountsTotal() {
                return this.selectedOtherAccounts().reduce((sum, item) => sum + (parseFloat(item.other_accounts) || 0), 0);
            },
            selectedPoNumber() {
                const row = this.rows.find((item) => String(item.purchase_order_id || '') === String(this.selectedPoId || ''));
                return row ? (row.po_no || '') : '';
            },
            formatQty(value) {
                return (Math.round((parseFloat(value) || 0) * 100000) / 100000).toLocaleString(undefined, {
                    minimumFractionDigits: 5,
                    maximumFractionDigits: 5
                });
            }
        };
    }
</script>

<div x-data="payableLedger()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold"> Ledger<?= $selectedSupplier ? ' for ' . esc((string) ($selectedSupplier['name'] ?? '')) : '' ?></h1>
            <p class="mt-1 text-sm muted">Shows payable balance with optional date range.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if ($selectedSupplier): ?>
                <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/supplier-orders') ?>">PO</a>
                <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/purchase-orders') ?>">Pickup</a>
                <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . $supplierId) ?>">Payments</a>
                <button class="btn btn-secondary" type="button" @click='openSupplierStatementModal(<?= (int) $supplierId ?>, <?= json_encode((string) ($selectedSupplier['name'] ?? ''), $jsonFlags) ?>, <?= json_encode((string) ($selectedSupplier['payment_term'] ?? ''), $jsonFlags) ?>)'>Payables</button>
                <button class="btn btn-secondary" type="button" @click="openForwardBalance()">Forward Balance</button>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>?q=<?= rawurlencode((string) ($selectedSupplier['name'] ?? '')) ?>">Back</a>
        </div>
    </div>

    <form class="filter-card mt-6 rounded border border-gray-200 p-4" method="get" action="<?= base_url('payable-ledger') ?>" x-data>
        <div class="flex flex-wrap items-end gap-3">
            <?php if ($selectedSupplier): ?>
                <input type="hidden" name="supplier_id" value="<?= esc((string) $supplierId) ?>">
            <?php endif; ?>
            <input type="hidden" name="start" x-ref="fromDate" value="<?= esc($start) ?>">
            <input type="hidden" name="end" x-ref="toDate" value="<?= esc($end) ?>">
            <div><label class="block text-sm font-medium" for="start">Start Date</label><input class="input mt-1" id="start" x-ref="fromDateDraft" type="date" value="<?= esc($start) ?>"></div>
            <div><label class="block text-sm font-medium" for="end">End Date</label><input class="input mt-1" id="end" x-ref="toDateDraft" type="date" value="<?= esc($end) ?>"></div>
            <div class="flex items-end gap-2">
                <?php if ($selectedSupplier): ?>
                    <button class="btn btn-strong" type="submit" @click="$refs.fromDate.value = $refs.fromDateDraft.value; $refs.toDate.value = $refs.toDateDraft.value">Filter</button>
                    <a class="btn btn-secondary" target="_blank" href="<?= base_url('payable-ledger/print') ?>?supplier_id=<?= esc((string) $supplierId) ?>&start=<?= esc($start) ?>&end=<?= esc($end) ?>">Print</a>
                    <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . $supplierId) ?>">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php if ($selectedSupplier): ?>
        <table class="table mt-6">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>RR#</th>
                    <th>CV#</th>
                    <th>Account Title</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Payables</th>
                    <th>Payment</th>
                    <th>Other Accounts</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Balance Forwarded</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><?= esc(number_format((float) $openingBalance, 2)) ?></td>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="11">No ledger rows in range.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                            <td><?= esc((string) $row['entry_date']) ?></td>
                            <td>
                                <?php if (! empty($row['purchase_order_id']) && (! empty($itemsByPurchaseOrder[$row['purchase_order_id']]) || ! empty($allocationsByPurchaseOrder[$row['purchase_order_id']]))): ?>
                                    <button class="btn-link" type="button" @click="openPoDetails(<?= (int) $row['purchase_order_id'] ?>)"><?= esc((string) ($row['po_no'] ?? '')) ?></button>
                                <?php else: ?>
                                    <?= esc((string) ($row['po_no'] ?? '')) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (! empty($row['payable_id']) && ! empty($allocationsByPayable[$row['payable_id']])): ?>
                                    <button class="btn-link" type="button" @click="openPayableAllocations(<?= (int) $row['payable_id'] ?>)"><?= esc((string) ($row['pr_no'] ?? '')) ?></button>
                                <?php else: ?>
                                    <?= esc((string) ($row['pr_no'] ?? '')) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= esc((string) ($row['account_title'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['qty'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['price'] ?? '')) ?></td>
                            <td><?= esc(number_format((float) ($row['payables'] ?? 0), 2)) ?></td>
                            <td><?= (float) ($row['payment'] ?? 0) > 0 ? esc(number_format((float) $row['payment'], 2)) : '' ?></td>
                            <td><?= (float) ($row['other_accounts'] ?? 0) > 0 ? esc(number_format((float) $row['other_accounts'], 2)) : '' ?></td>
                            <td><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="mt-4 flex items-center justify-between gap-4 text-sm muted">
                <div>Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?></div>
                <div class="flex items-center gap-2">
                    <?php if (($currentPage ?? 1) > 1): ?><a class="btn btn-secondary" href="<?= base_url('payable-ledger?' . http_build_query(['supplier_id' => $supplierId, 'start' => $start, 'end' => $end, 'page' => $currentPage - 1])) ?>">Previous</a><?php endif; ?>
                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?><a class="btn btn-secondary" href="<?= base_url('payable-ledger?' . http_build_query(['supplier_id' => $supplierId, 'start' => $start, 'end' => $end, 'page' => $currentPage + 1])) ?>">Next</a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="card p-4 total-highlight">
                <div class="flex justify-between"><span>Current Balance</span><span><?= esc(number_format((float) ($currentBalance ?? $openingBalance ?? 0), 2)) ?></span></div>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-6 card p-4 text-sm">
            Select a supplier from the Suppliers page to view its payable ledger.
        </div>
    <?php endif; ?>

    <div class="modal-backdrop" x-show="forwardBalanceOpen" x-cloak @click.self="closeForwardBalance()">
        <div class="modal-panel max-w-lg p-6" @click.stop>
            <h2 class="text-lg font-semibold">Forward Balance</h2>
            <p class="mt-1 text-sm muted">Update the starting balance used for this ledger.</p>
            <form class="mt-4 space-y-4" method="post" action="<?= base_url('payable-ledger/forward-balance') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="supplier_id" value="<?= esc((string) $supplierId) ?>">
                <div>
                    <label class="block text-sm font-medium" for="forward_balance">Forwarded Balance</label>
                    <input class="input mt-1" id="forward_balance" name="forwarded_balance" type="number" step="0.01" inputmode="decimal" x-model="forwardBalanceAmount">
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button class="btn btn-secondary" type="button" @click="closeForwardBalance()">Cancel</button>
                    <button class="btn" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" x-show="poDetailsOpen" x-cloak @click.self="closePoDetails()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-4 border-b pb-4">
                <h2 class="text-lg font-semibold">RR Details: <span x-text="selectedPoNumber()"></span></h2>
            </div>
            <div class="modal-split">
                <div>
                    <h3 class="mb-3 font-semibold">Pickup Items</h3>
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
                                    <td colspan="4">No items found.</td>
                                </tr>
                            </template>
                            <template x-for="item in selectedItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name"></td>
                                    <td x-text="formatQty(item.qty)"></td>
                                    <td x-text="Number(item.unit_price || 0).toFixed(2)"></td>
                                    <td x-text="Number(item.line_total || 0).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="mb-3 font-semibold">CV Allocations</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>CV #</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody><template x-if="selectedPoAllocations().length === 0">
                                <tr>
                                    <td colspan="3">No allocations found.</td>
                                </tr>
                            </template><template x-for="(alloc, index) in selectedPoAllocations()" :key="index">
                                <tr>
                                    <td x-text="alloc.pr_no"></td>
                                    <td x-text="alloc.date"></td>
                                    <td x-text="Number(alloc.amount).toFixed(2)"></td>
                                </tr>
                            </template></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak @click.self="closeAllocations()">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold">CV Summary <span x-text="selectedPayable() ? selectedPayable().pr_no : ''"></span></h2><button class="btn btn-secondary" type="button" @click="closeAllocations()">Close</button>
            </div>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div class="card p-3">
                    <p class="muted">Original Amount Paid</p>
                    <p class="font-semibold" x-text="selectedPayable() ? Number(selectedPayable().amount_received || 0).toFixed(2) : '0.00'"></p>
                </div>
                <div class="card p-3">
                    <p class="muted">Allocated to RRs</p>
                    <p class="font-semibold" x-text="selectedAllocatedTotal().toFixed(2)"></p>
                </div>
                <div class="card p-3">
                    <p class="muted">Other Accounts</p>
                    <p class="font-semibold" x-text="selectedOtherAccountsTotal().toFixed(2)"></p>
                </div>
            </div>
            <div class="modal-split mt-5">
                <div>
                    <h3 class="text-sm font-semibold">RR Allocations</h3>
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>RR#</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody><template x-if="selectedAllocations().length === 0">
                                <tr>
                                    <td colspan="3">No allocations found.</td>
                                </tr>
                            </template><template x-for="(allocation, index) in selectedAllocations()" :key="index">
                                <tr>
                                    <td x-text="allocation.po_no"></td>
                                    <td x-text="allocation.date"></td>
                                    <td x-text="Number(allocation.amount).toFixed(2)"></td>
                                </tr>
                            </template></tbody>
                    </table>
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
                        <tbody><template x-if="selectedOtherAccounts().length === 0">
                                <tr>
                                    <td colspan="2">No other accounts found.</td>
                                </tr>
                            </template><template x-for="(item, index) in selectedOtherAccounts()" :key="index">
                                <tr>
                                    <td x-text="item.account_title"></td>
                                    <td x-text="Number(item.other_accounts || 0).toFixed(2)"></td>
                                </tr>
                            </template></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?= view('suppliers/_statement_modal') ?>
</div>
<?= $this->endSection() ?>
