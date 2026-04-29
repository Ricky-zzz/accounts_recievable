<?php
/**
 * @var list<array{id: int|string, name: string}> $suppliers
 * @var array{id: int|string, name: string}|null $selectedSupplier
 * @var int $supplierId
 * @var string $start
 * @var string $end
 * @var int|float|string $openingBalance
 * @var list<array<string, int|float|string|null>> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $totalPages
 * @var int $rowOffset
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByPurchaseOrder
 * @var array<int|string, int> $itemCounts
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPurchaseOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPayable
 * @var array<int|string, list<array<string, int|float|string|null>>> $otherAccountsByPayable
 * @var array<int|string, array<string, int|float|string|null>> $payablesById
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
?>
<script>
    window.payableLedgerData = {
        itemsByPurchaseOrder: <?= $itemsJson ?>,
        allocationsByPurchaseOrder: <?= $poAllocJson ?>,
        allocationsByPayable: <?= $payableAllocJson ?>,
        otherAccountsByPayable: <?= $payableOtherJson ?>,
        payablesById: <?= $payablesByIdJson ?>
    };
    function payableLedger() {
        return {
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
            openPoDetails(id) { this.selectedPoId = id; this.poDetailsOpen = true; this.allocOpen = false; },
            closePoDetails() { this.poDetailsOpen = false; this.selectedPoId = null; },
            openPayableAllocations(id) { this.allocType = 'payable'; this.selectedPayableId = id; this.allocOpen = true; this.poDetailsOpen = false; },
            closeAllocations() { this.allocOpen = false; this.allocType = ''; this.selectedPayableId = null; },
            selectedItems() { return this.itemsByPurchaseOrder[this.selectedPoId] || []; },
            selectedPoAllocations() { return this.allocationsByPurchaseOrder[this.selectedPoId] || []; },
            selectedPayable() { return this.payablesById[this.selectedPayableId] || null; },
            selectedAllocations() { return this.allocationsByPayable[this.selectedPayableId] || []; },
            selectedOtherAccounts() { return this.otherAccountsByPayable[this.selectedPayableId] || []; },
            selectedAllocatedTotal() { return this.selectedAllocations().reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0); },
            selectedOtherAccountsTotal() { return this.selectedOtherAccounts().reduce((sum, item) => sum + (parseFloat(item.other_accounts) || 0), 0); },
            selectedPoNumber() { return ''; }
        };
    }
</script>

<div x-data="payableLedger()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Supplier Ledger<?= $selectedSupplier ? ' for ' . esc((string) ($selectedSupplier['name'] ?? '')) : '' ?></h1>
            <p class="mt-1 text-sm muted">Shows payable balance with optional date range.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($selectedSupplier): ?>
                <a class="btn btn-secondary" href="<?= base_url('suppliers/' . $supplierId . '/purchase-orders') ?>">Orders</a>
                <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . $supplierId) ?>">Payables</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>">Back</a>
        </div>
    </div>

    <form class="mt-6 flex flex-wrap items-end gap-3" method="get" action="<?= base_url('payable-ledger') ?>">
        <div>
            <label class="block text-sm font-medium" for="supplier_id">Supplier</label>
            <select class="input mt-1" id="supplier_id" name="supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= esc((string) $supplier['id']) ?>" <?= (string) $supplierId === (string) $supplier['id'] ? 'selected' : '' ?>><?= esc((string) $supplier['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-sm font-medium" for="start">Start Date</label><input class="input mt-1" id="start" name="start" type="date" value="<?= esc($start) ?>"></div>
        <div><label class="block text-sm font-medium" for="end">End Date</label><input class="input mt-1" id="end" name="end" type="date" value="<?= esc($end) ?>"></div>
        <div class="flex items-end gap-2">
            <button class="btn" type="submit">Filter</button>
            <?php if ($selectedSupplier): ?>
                <a class="btn btn-secondary" target="_blank" href="<?= base_url('payable-ledger/print') ?>?supplier_id=<?= esc((string) $supplierId) ?>&start=<?= esc($start) ?>&end=<?= esc($end) ?>">Print PDF</a>
                <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . $supplierId) ?>">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($selectedSupplier): ?>
        <div class="mt-6 card p-4 text-sm"><div class="flex justify-between"><span>Last Balance</span><span><?= esc(number_format((float) $openingBalance, 2)) ?></span></div></div>
        <table class="table mt-6">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>PO#</th>
                    <th>PR#</th>
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
                <?php if (empty($rows)): ?>
                    <tr><td colspan="11">No ledger rows in range.</td></tr>
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
                            <td><?= esc((string) ($row['qty'] ?? '')) ?><?= ! empty($row['purchase_order_id']) && ($itemCounts[$row['purchase_order_id']] ?? 0) > 1 ? ' +' : '' ?></td>
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
    <?php endif; ?>

    <div class="modal-backdrop" x-show="poDetailsOpen" x-cloak @click.self="closePoDetails()">
        <div class="modal-panel max-w-4xl p-6" @click.stop>
            <div class="mb-4 border-b pb-4"><h2 class="text-lg font-semibold">PO Details</h2></div>
            <div class="grid grid-cols-2 gap-6">
                <div><h3 class="mb-3 font-semibold">Purchase Items</h3><table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody><template x-if="selectedItems().length === 0"><tr><td colspan="4">No items found.</td></tr></template><template x-for="item in selectedItems()" :key="item.id"><tr><td x-text="item.product_name"></td><td x-text="item.qty"></td><td x-text="Number(item.unit_price).toFixed(2)"></td><td x-text="Number(item.line_total).toFixed(2)"></td></tr></template></tbody></table></div>
                <div><h3 class="mb-3 font-semibold">PO Allocations</h3><table class="table"><thead><tr><th>PR #</th><th>Date</th><th>Amount</th></tr></thead><tbody><template x-if="selectedPoAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template><template x-for="(alloc, index) in selectedPoAllocations()" :key="index"><tr><td x-text="alloc.pr_no"></td><td x-text="alloc.date"></td><td x-text="Number(alloc.amount).toFixed(2)"></td></tr></template></tbody></table></div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak @click.self="closeAllocations()">
        <div class="modal-panel max-w-3xl p-6" @click.stop>
            <div class="flex items-start justify-between gap-4"><h2 class="text-lg font-semibold">PR Summary <span x-text="selectedPayable() ? selectedPayable().pr_no : ''"></span></h2><button class="btn btn-secondary" type="button" @click="closeAllocations()">Close</button></div>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div class="card p-3"><p class="muted">Original Amount Paid</p><p class="font-semibold" x-text="selectedPayable() ? Number(selectedPayable().amount_received || 0).toFixed(2) : '0.00'"></p></div>
                <div class="card p-3"><p class="muted">Allocated to POs</p><p class="font-semibold" x-text="selectedAllocatedTotal().toFixed(2)"></p></div>
                <div class="card p-3"><p class="muted">Other Accounts</p><p class="font-semibold" x-text="selectedOtherAccountsTotal().toFixed(2)"></p></div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div><h3 class="text-sm font-semibold">PO Allocations</h3><table class="table mt-3"><thead><tr><th>PO#</th><th>Date</th><th>Amount</th></tr></thead><tbody><template x-if="selectedAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template><template x-for="(allocation, index) in selectedAllocations()" :key="index"><tr><td x-text="allocation.po_no"></td><td x-text="allocation.date"></td><td x-text="Number(allocation.amount).toFixed(2)"></td></tr></template></tbody></table></div>
                <div><h3 class="text-sm font-semibold">Other Accounts</h3><table class="table mt-3"><thead><tr><th>Account Title</th><th>Amount</th></tr></thead><tbody><template x-if="selectedOtherAccounts().length === 0"><tr><td colspan="2">No other accounts found.</td></tr></template><template x-for="(item, index) in selectedOtherAccounts()" :key="index"><tr><td x-text="item.account_title"></td><td x-text="Number(item.other_accounts || 0).toFixed(2)"></td></tr></template></tbody></table></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
