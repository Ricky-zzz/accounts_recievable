<?php
/**
 * @var array{id: int|string, name: string}|null $selectedSupplier
 * @var int $supplierId
 * @var string $start
 * @var string $end
 * @var int|float|string $openingBalance
 * @var int|float|string $openingOpenBalance
 * @var int|float|string $currentBalance
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
 * @var array<int|string, array<string, int|float|string|null>> $supplierOrdersById
 * @var array<int|string, list<array<string, int|float|string|null>>> $supplierOrderItemsByOrder
 * @var array<int|string, list<array<string, int|float|string|null>>> $supplierOrderConsumptionsByOrder
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
$supplierOrdersByIdJson = json_encode($supplierOrdersById ?? [], $jsonFlags);
$supplierOrderItemsJson = json_encode($supplierOrderItemsByOrder ?? [], $jsonFlags);
$supplierOrderConsumptionsJson = json_encode($supplierOrderConsumptionsByOrder ?? [], $jsonFlags);
$rowsJson = json_encode($rows ?? [], $jsonFlags);
?>
<script>
    window.payableLedgerData = {
        rows: <?= $rowsJson ?>,
        itemsByPurchaseOrder: <?= $itemsJson ?>,
        allocationsByPurchaseOrder: <?= $poAllocJson ?>,
        allocationsByPayable: <?= $payableAllocJson ?>,
        otherAccountsByPayable: <?= $payableOtherJson ?>,
        payablesById: <?= $payablesByIdJson ?>,
        supplierOrdersById: <?= $supplierOrdersByIdJson ?>,
        supplierOrderItemsByOrder: <?= $supplierOrderItemsJson ?>,
        supplierOrderConsumptionsByOrder: <?= $supplierOrderConsumptionsJson ?>
    };
    function payableLedger() {
        return {
            ...supplierStatementModalState(),
            rows: window.payableLedgerData.rows,
            itemsByPurchaseOrder: window.payableLedgerData.itemsByPurchaseOrder,
            allocationsByPurchaseOrder: window.payableLedgerData.allocationsByPurchaseOrder,
            allocationsByPayable: window.payableLedgerData.allocationsByPayable,
            otherAccountsByPayable: window.payableLedgerData.otherAccountsByPayable,
            payablesById: window.payableLedgerData.payablesById,
            supplierOrdersById: window.payableLedgerData.supplierOrdersById,
            supplierOrderItemsByOrder: window.payableLedgerData.supplierOrderItemsByOrder,
            supplierOrderConsumptionsByOrder: window.payableLedgerData.supplierOrderConsumptionsByOrder,
            poDetailsOpen: false,
            supplierOrderOpen: false,
            allocOpen: false,
            allocType: '',
            selectedPoId: null,
            selectedSupplierOrderId: null,
            selectedPayableId: null,
            openPoDetails(id) { this.selectedPoId = id; this.poDetailsOpen = true; this.supplierOrderOpen = false; this.allocOpen = false; },
            closePoDetails() { this.poDetailsOpen = false; this.selectedPoId = null; },
            openSupplierOrder(id) { this.selectedSupplierOrderId = id; this.supplierOrderOpen = true; this.poDetailsOpen = false; this.allocOpen = false; },
            closeSupplierOrder() { this.supplierOrderOpen = false; this.selectedSupplierOrderId = null; },
            openPayableAllocations(id) { this.allocType = 'payable'; this.selectedPayableId = id; this.allocOpen = true; this.poDetailsOpen = false; this.supplierOrderOpen = false; },
            closeAllocations() { this.allocOpen = false; this.allocType = ''; this.selectedPayableId = null; },
            selectedItems() { return this.itemsByPurchaseOrder[this.selectedPoId] || []; },
            selectedPoAllocations() { return this.allocationsByPurchaseOrder[this.selectedPoId] || []; },
            selectedSupplierOrder() { return this.supplierOrdersById[this.selectedSupplierOrderId] || null; },
            selectedSupplierOrderItems() { return this.supplierOrderItemsByOrder[this.selectedSupplierOrderId] || []; },
            selectedSupplierOrderConsumptions() { return this.supplierOrderConsumptionsByOrder[this.selectedSupplierOrderId] || []; },
            selectedPayable() { return this.payablesById[this.selectedPayableId] || null; },
            selectedAllocations() { return this.allocationsByPayable[this.selectedPayableId] || []; },
            selectedOtherAccounts() { return this.otherAccountsByPayable[this.selectedPayableId] || []; },
            selectedAllocatedTotal() { return this.selectedAllocations().reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0); },
            selectedOtherAccountsTotal() { return this.selectedOtherAccounts().reduce((sum, item) => sum + (parseFloat(item.other_accounts) || 0), 0); },
            selectedPoNumber() {
                const row = this.rows.find((item) => String(item.purchase_order_id || '') === String(this.selectedPoId || ''));
                return row ? (row.po_no || '') : '';
            },
            formatQty(value) { return (Math.round((parseFloat(value) || 0) * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
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
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>?q=<?= rawurlencode((string) ($selectedSupplier['name'] ?? '')) ?>">Back</a>
        </div>
    </div>

    <form class="filter-card mt-6 rounded border border-gray-200 p-4" method="get" action="<?= base_url('payable-ledger') ?>" x-data>
        <div class="flex flex-wrap items-end gap-3">
        <?php if ($selectedSupplier): ?>
            <input type="hidden" name="supplier_id" value="<?= esc((string) $supplierId) ?>">
        <?php endif; ?>
        <div><label class="block text-sm font-medium" for="start">Start Date</label><input class="input mt-1" id="start" name="start" type="date" value="<?= esc($start) ?>" @change="$el.form.requestSubmit()"></div>
        <div><label class="block text-sm font-medium" for="end">End Date</label><input class="input mt-1" id="end" name="end" type="date" value="<?= esc($end) ?>" @change="$el.form.requestSubmit()"></div>
        <div class="flex items-end gap-2">
            <?php if ($selectedSupplier): ?>
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
                    <th>PO#</th>
                    <th>RR#</th>
                    <th>CV#</th>
                    <th>Account Title</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Payables</th>
                    <th>Payment</th>
                    <th>Other Accounts</th>
                    <th>PO Balance</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
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
                    <td><?= esc(number_format((float) ($openingOpenBalance ?? 0), 2)) ?></td>
                    <td><?= esc(number_format((float) $openingBalance, 2)) ?></td>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="13">No ledger rows in range.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php
                        $isRrRow = ! empty($row['purchase_order_id']);
                        $isSupplierPoRow = ! $isRrRow && empty($row['payable_id']) && ! empty($row['supplier_order_id']);
                        ?>
                        <tr>
                            <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                            <td><?= esc((string) $row['entry_date']) ?></td>
                            <td>
                                <?php if ($isSupplierPoRow): ?>
                                    <button class="btn-link" type="button" @click="openSupplierOrder(<?= (int) $row['supplier_order_id'] ?>)"><?= esc((string) ($row['supplier_order_po_no'] ?? '')) ?></button>
                                <?php endif; ?>
                            </td>
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
                            <td><?= esc(number_format((float) ($row['total_open_balance'] ?? 0), 2)) ?></td>
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
            <div class="card p-4 total-highlight"><div class="flex justify-between"><span>Current Balance</span><span><?= esc(number_format((float) ($currentBalance ?? $openingBalance ?? 0), 2)) ?></span></div></div>
        </div>
    <?php else: ?>
        <div class="mt-6 card p-4 text-sm">
            Select a supplier from the Suppliers page to view its payable ledger.
        </div>
    <?php endif; ?>

    <div class="modal-backdrop" x-show="poDetailsOpen" x-cloak @click.self="closePoDetails()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-4 border-b pb-4"><h2 class="text-lg font-semibold">RR Details: <span x-text="selectedPoNumber()"></span></h2></div>
            <div class="modal-split">
                <div>
                    <h3 class="mb-3 font-semibold">Pickup Items</h3>
                    <table class="table">
                        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Source PO</th><th>PO Balance After</th></tr></thead>
                        <tbody>
                            <template x-if="selectedItems().length === 0"><tr><td colspan="6">No items found.</td></tr></template>
                            <template x-for="item in selectedItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name"></td>
                                    <td x-text="formatQty(item.qty)"></td>
                                    <td x-text="Number(item.unit_price || 0).toFixed(2)"></td>
                                    <td x-text="Number(item.line_total || 0).toFixed(2)"></td>
                                    <td>
                                        <template x-if="item.supplier_order_id"><button class="btn-link" type="button" @click="openSupplierOrder(item.supplier_order_id)" x-text="item.supplier_order_po_no || ''"></button></template>
                                        <template x-if="!item.supplier_order_id"><span>No source PO</span></template>
                                    </td>
                                    <td x-text="item.po_qty_balance_after !== null && item.po_qty_balance_after !== undefined ? formatQty(item.po_qty_balance_after) : ''"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div><h3 class="mb-3 font-semibold">CV Allocations</h3><table class="table"><thead><tr><th>CV #</th><th>Date</th><th>Amount</th></tr></thead><tbody><template x-if="selectedPoAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template><template x-for="(alloc, index) in selectedPoAllocations()" :key="index"><tr><td x-text="alloc.pr_no"></td><td x-text="alloc.date"></td><td x-text="Number(alloc.amount).toFixed(2)"></td></tr></template></tbody></table></div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="supplierOrderOpen" x-cloak @click.self="closeSupplierOrder()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-4 border-b pb-4">
                <div>
                    <h2 class="text-lg font-semibold">Supplier PO <span x-text="selectedSupplierOrder() ? selectedSupplierOrder().po_no : ''"></span></h2>
                    <p class="mt-1 text-sm muted" x-text="selectedSupplierOrder() ? selectedSupplierOrder().supplier_name || '' : ''"></p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeSupplierOrder()">Close</button>
            </div>
            <div class="modal-split">
                <div>
                    <h3 class="mb-3 text-sm font-semibold">PO Items</h3>
                    <table class="table">
                        <thead><tr><th>Product</th><th class="text-right">Ordered</th><th class="text-right">Picked-Up</th><th class="text-right">Balance</th></tr></thead>
                        <tbody>
                            <template x-if="selectedSupplierOrderItems().length === 0"><tr><td colspan="4">No items found.</td></tr></template>
                            <template x-for="item in selectedSupplierOrderItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name"></td>
                                    <td class="text-right" x-text="formatQty(item.qty_ordered)"></td>
                                    <td class="text-right" x-text="formatQty(item.qty_picked_up)"></td>
                                    <td class="text-right" x-text="formatQty(item.qty_balance)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="mb-3 text-sm font-semibold">RRs Consumed</h3>
                    <table class="table">
                        <thead><tr><th>RR#</th><th>Date</th><th>Product</th><th class="text-right">Qty</th><th class="text-right">PO Balance</th></tr></thead>
                        <tbody>
                            <template x-if="selectedSupplierOrderConsumptions().length === 0"><tr><td colspan="5">No pickups consumed this PO yet.</td></tr></template>
                            <template x-for="item in selectedSupplierOrderConsumptions()" :key="item.purchase_order_id + '-' + item.product_name + '-' + item.qty">
                                <tr>
                                    <td><button class="btn-link" type="button" @click="openPoDetails(item.purchase_order_id)" x-text="item.rr_no"></button></td>
                                    <td x-text="item.rr_date"></td>
                                    <td x-text="item.product_name"></td>
                                    <td class="text-right" x-text="formatQty(item.qty)"></td>
                                    <td class="text-right" x-text="item.po_qty_balance_after !== null && item.po_qty_balance_after !== undefined ? formatQty(item.po_qty_balance_after) : ''"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak @click.self="closeAllocations()">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="flex items-start justify-between gap-4"><h2 class="text-lg font-semibold">CV Summary <span x-text="selectedPayable() ? selectedPayable().pr_no : ''"></span></h2><button class="btn btn-secondary" type="button" @click="closeAllocations()">Close</button></div>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div class="card p-3"><p class="muted">Original Amount Paid</p><p class="font-semibold" x-text="selectedPayable() ? Number(selectedPayable().amount_received || 0).toFixed(2) : '0.00'"></p></div>
                <div class="card p-3"><p class="muted">Allocated to RRs</p><p class="font-semibold" x-text="selectedAllocatedTotal().toFixed(2)"></p></div>
                <div class="card p-3"><p class="muted">Other Accounts</p><p class="font-semibold" x-text="selectedOtherAccountsTotal().toFixed(2)"></p></div>
            </div>
            <div class="modal-split mt-5">
                <div><h3 class="text-sm font-semibold">RR Allocations</h3><table class="table mt-3"><thead><tr><th>RR#</th><th>Date</th><th>Amount</th></tr></thead><tbody><template x-if="selectedAllocations().length === 0"><tr><td colspan="3">No allocations found.</td></tr></template><template x-for="(allocation, index) in selectedAllocations()" :key="index"><tr><td x-text="allocation.po_no"></td><td x-text="allocation.date"></td><td x-text="Number(allocation.amount).toFixed(2)"></td></tr></template></tbody></table></div>
                <div><h3 class="text-sm font-semibold">Other Accounts</h3><table class="table mt-3"><thead><tr><th>Account Title</th><th>Amount</th></tr></thead><tbody><template x-if="selectedOtherAccounts().length === 0"><tr><td colspan="2">No other accounts found.</td></tr></template><template x-for="(item, index) in selectedOtherAccounts()" :key="index"><tr><td x-text="item.account_title"></td><td x-text="Number(item.other_accounts || 0).toFixed(2)"></td></tr></template></tbody></table></div>
            </div>
        </div>
    </div>

    <?= view('suppliers/_statement_modal') ?>
</div>
<?= $this->endSection() ?>
