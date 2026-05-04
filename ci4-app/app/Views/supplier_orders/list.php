<?php
/**
 * @var array{id: int|string, name: string, payment_term?: int|string|null}|null $supplier
 * @var int|null $supplierId
 * @var array{id?: int|string, name?: string|null}|null $selectedSupplierFilter
 * @var string $fromDate
 * @var string $toDate
 * @var string $poNo
 * @var list<array<string, int|float|string|null>> $orders
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByOrder
 * @var int|float|string $totalOrdered
 * @var int|float|string $totalPickedUp
 * @var int|float|string $totalBalance
 * @var string $pagerLinks
 * @var int $rowOffset
 * @var array<string, mixed> $formData
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$ordersJson = json_encode($orders ?? [], $jsonFlags);
$itemsJson = json_encode($itemsByOrder ?? [], $jsonFlags);
$products = $formData['products'] ?? [];
$suppliers = $formData['suppliers'] ?? [];
$selectedSupplier = $formData['selectedSupplier'] ?? null;
$productsJson = json_encode($products, $jsonFlags);
$suppliersJson = json_encode($suppliers, $jsonFlags);
$listUrl = $supplier ? base_url('suppliers/' . ($supplier['id'] ?? 0) . '/supplier-orders') : base_url('supplier-orders');
$printUrl = $supplier ? base_url('suppliers/' . ($supplier['id'] ?? 0) . '/supplier-orders/print') : base_url('supplier-orders/print');
$printParams = [
    'from_date' => $fromDate ?? '',
    'to_date' => $toDate ?? '',
    'po_no' => $poNo ?? '',
];
if (! $supplier && ! empty($supplierId)) {
    $printParams['supplier_id'] = $supplierId;
}
?>

<div x-data="supplierOrderList()" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">
                <?= $supplier ? 'Purchase Orders for ' . esc((string) ($supplier['name'] ?? '')) : 'Purchase Orders' ?>
            </h1>
            <p class="mt-1 text-sm muted">Quantity-only supplier orders before pickup.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button class="btn btn-strong" type="button" @click="openCreate()">New PO</button>
            <a class="btn btn-secondary" href="<?= $supplier ? base_url('suppliers/' . ($supplier['id'] ?? 0) . '/purchase-orders') : base_url('purchase-orders') ?>">Pickup</a>
            <a class="btn btn-secondary" href="<?= $supplier ? base_url('payables/supplier/' . ($supplier['id'] ?? 0)) : base_url('payables') ?>">Payments</a>
            <?php if ($supplier): ?>
                <button class="btn btn-secondary" type="button" @click='openSupplierStatementModal(<?= (int) ($supplier['id'] ?? 0) ?>, <?= json_encode((string) ($supplier['name'] ?? ''), $jsonFlags) ?>, <?= json_encode((string) ($supplier['payment_term'] ?? ''), $jsonFlags) ?>)'>Payables</button>
                <a class="btn btn-secondary" href="<?= base_url('payable-ledger?supplier_id=' . ($supplier['id'] ?? 0)) ?>">Ledger</a>
            <?php endif; ?>
            <a class="btn btn-secondary" target="_blank" href="<?= esc($printUrl . '?' . http_build_query($printParams)) ?>">Print</a>
            <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= esc($listUrl) ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
        <div class="grid gap-4 <?= $supplier ? 'md:grid-cols-4' : 'md:grid-cols-5' ?>">
            <?php if (! $supplier): ?>
                <div>
                    <label class="block text-sm font-medium" for="supplier_id">Supplier</label>
                    <select class="input mt-1" id="supplier_id" name="supplier_id" @change="$el.form.requestSubmit()">
                        <option value="">All suppliers</option>
                        <?php foreach ($suppliers as $row): ?>
                            <option value="<?= esc((string) $row['id']) ?>" <?= (string) ($supplierId ?? '') === (string) $row['id'] ? 'selected' : '' ?>><?= esc((string) $row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium" for="po_no">PO Number</label>
                <input class="input mt-1" id="po_no" name="po_no" value="<?= esc($poNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
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

    <table class="table mt-6">
        <colgroup>
            <col class="w-12">
            <col class="w-32">
            <?php if (! $supplier): ?><col><?php endif; ?>
            <col class="w-32">
            <col class="w-40">
            <col class="w-40">
            <col class="w-40">
            <col class="w-36">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <?php if (! $supplier): ?><th>Supplier</th><?php endif; ?>
                <th>PO#</th>
                <th class="text-right">Ordered Qty</th>
                <th class="text-right">Picked-Up Qty</th>
                <th class="text-right">Balance Qty</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="<?= $supplier ? 7 : 8 ?>">No supplier POs found.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $index => $order): ?>
                    <tr>
                        <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                        <td><?= esc((string) ($order['date'] ?? '')) ?></td>
                        <?php if (! $supplier): ?><td><?= esc((string) ($order['supplier_name'] ?? '')) ?></td><?php endif; ?>
                        <td><button class="btn-link" type="button" @click="openDetails(<?= (int) $order['id'] ?>)"><?= esc((string) ($order['po_no'] ?? '')) ?></button></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['qty_ordered_total'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['qty_picked_up_total'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['qty_balance_total'] ?? 0), 2)) ?></td>
                        <td class="text-center">
                            <div class="flex flex-wrap justify-center gap-2">
                                <button class="btn btn-secondary" type="button" @click="openEdit(<?= (int) $order['id'] ?>)" <?= (float) ($order['qty_picked_up_total'] ?? 0) > 0 || ($order['status'] ?? '') === 'voided' ? 'disabled' : '' ?>>Edit</button>
                                <button class="btn btn-secondary" type="button" @click="openVoid(<?= (int) $order['id'] ?>)" <?= (float) ($order['qty_picked_up_total'] ?? 0) > 0 || ($order['status'] ?? '') === 'voided' ? 'disabled' : '' ?>>Void</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card p-4">
            <p class="muted">Total Ordered</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($totalOrdered ?? 0), 2)) ?></p>
        </div>
        <div class="card p-4">
            <p class="muted">Total Picked</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($totalPickedUp ?? 0), 2)) ?></p>
        </div>
        <div class="card p-4">
            <p class="muted">Total Balance</p>
            <p class="mt-1 text-lg font-semibold"><?= esc(number_format((float) ($totalBalance ?? 0), 2)) ?></p>
        </div>
    </div>

    <?php if (! empty($pagerLinks ?? '')): ?><div class="flex justify-end"><?= $pagerLinks ?></div><?php endif; ?>

    <div class="modal-backdrop" x-show="formOpen" x-cloak @click.self="closeForm()">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Supplier PO' : 'New Supplier PO'"></h2>
                    <p class="mt-1 text-sm muted">Use this for product quantity reservation only.</p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeForm()">Close</button>
            </div>

            <form method="post" :action="formAction" class="space-y-6">
                <?= csrf_field() ?>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium" for="supplier_id">Supplier</label>
                        <?php if ($selectedSupplier): ?>
                            <div class="mt-1 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm"><?= esc((string) $selectedSupplier['name']) ?></div>
                            <input type="hidden" name="supplier_id" x-model="form.supplier_id">
                        <?php else: ?>
                            <select class="input mt-1" id="supplier_id" name="supplier_id" x-model="form.supplier_id" :disabled="isEdit" required>
                                <option value="">Select supplier</option>
                                <?php foreach ($suppliers as $row): ?>
                                    <option value="<?= esc((string) $row['id']) ?>"><?= esc((string) $row['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="po_no_input">PO Number</label>
                        <input class="input mt-1" id="po_no_input" name="po_no" x-model="form.po_no" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="date_input">Date</label>
                        <input class="input mt-1" id="date_input" name="date" type="date" x-model="form.date" required>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Items</h3>
                        <button class="btn btn-secondary" type="button" @click="addItem()">Add Item</button>
                    </div>
                    <div class="mt-4 space-y-4">
                        <template x-for="(item, index) in formItems" :key="index">
                            <div class="grid gap-3 sm:grid-cols-5">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium">Product</label>
                                    <select class="input mt-1" x-model="item.product_id" :name="'items[' + index + '][product_id]'" required>
                                        <option value="">Select product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= esc((string) $product['id']) ?>"><?= esc((string) $product['product_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium">Qty</label>
                                    <input class="input mt-1" type="number" step="0.01" min="0" x-model="item.qty_ordered" :name="'items[' + index + '][qty_ordered]'" required>
                                </div>
                                <div class="flex items-end">
                                    <button class="btn btn-secondary" type="button" @click="formItems.splice(index, 1)" x-show="formItems.length > 1">Remove</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button class="btn btn-strong" type="submit" x-text="isEdit ? 'Save Changes' : 'Save Supplier PO'"></button>
                    <button class="btn btn-secondary" type="button" @click="closeForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" x-show="detailsOpen" x-cloak @click.self="closeDetails()">
        <div class="modal-panel max-w-4xl p-6" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-4 border-b pb-4">
                <div>
                    <h2 class="text-lg font-semibold">PO Details <span x-text="selectedOrder() ? selectedOrder().po_no : ''"></span></h2>
                    <p class="mt-1 text-sm muted" x-text="selectedOrder() ? selectedOrder().supplier_name || '' : ''"></p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeDetails()">Close</button>
            </div>
            <table class="table">
                <thead><tr><th>Product</th><th class="text-right">Ordered</th><th class="text-right">Picked-Up</th><th class="text-right">Balance</th></tr></thead>
                <tbody>
                    <template x-if="selectedItems().length === 0"><tr><td colspan="4">No items found.</td></tr></template>
                    <template x-for="item in selectedItems()" :key="item.id">
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
    </div>

    <div class="modal-backdrop" x-show="voidOpen" x-cloak @click.self="closeVoid()">
        <div class="modal-panel max-w-xl p-6" @click.stop>
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Void Supplier PO</h2>
                    <p class="mt-1 text-sm muted">Only unused supplier POs can be voided.</p>
                </div>
                <button class="btn btn-secondary" type="button" @click="closeVoid()">Close</button>
            </div>
            <form method="post" :action="selectedActionOrder() ? '<?= base_url('supplier-orders') ?>/' + selectedActionOrder().id + '/void' : '#'" class="space-y-4">
                <?= csrf_field() ?>
                <div class="card p-4 text-sm">
                    <div class="flex justify-between"><span>PO#</span><span x-text="selectedActionOrder() ? selectedActionOrder().po_no : ''"></span></div>
                    <div class="mt-2 flex justify-between"><span>Balance Qty</span><span x-text="selectedActionOrder() ? formatQty(selectedActionOrder().qty_balance_total) : '0.00'"></span></div>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="void_reason">Reason</label>
                    <textarea class="input mt-1" id="void_reason" name="void_reason" rows="3" required></textarea>
                </div>
                <div class="flex gap-3">
                    <button class="btn" type="submit">Void Supplier PO</button>
                    <button class="btn btn-secondary" type="button" @click="closeVoid()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?= view('suppliers/_statement_modal') ?>
</div>

<script>
    function supplierOrderList() {
        return {
            ...supplierStatementModalState(),
            orders: <?= $ordersJson ?>,
            itemsByOrder: <?= $itemsJson ?>,
            products: <?= $productsJson ?>,
            suppliers: <?= $suppliersJson ?>,
            formOpen: false,
            detailsOpen: false,
            voidOpen: false,
            isEdit: false,
            selectedOrderId: null,
            actionOrderId: null,
            formAction: '<?= base_url('supplier-orders') ?>',
            form: {
                supplier_id: '<?= esc((string) ($selectedSupplier['id'] ?? old('supplier_id') ?? '')) ?>',
                po_no: '',
                date: '<?= esc(date('Y-m-d')) ?>',
            },
            formItems: [{ product_id: '', qty_ordered: 1 }],
            openCreate() {
                this.isEdit = false;
                this.formAction = '<?= base_url('supplier-orders') ?>';
                this.form = {
                    supplier_id: '<?= esc((string) ($selectedSupplier['id'] ?? '')) ?>',
                    po_no: '',
                    date: '<?= esc(date('Y-m-d')) ?>',
                };
                this.formItems = [{ product_id: '', qty_ordered: 1 }];
                this.formOpen = true;
            },
            openEdit(id) {
                const order = this.orders.find((row) => String(row.id) === String(id));
                if (!order) return;
                this.isEdit = true;
                this.formAction = `<?= base_url('supplier-orders') ?>/${order.id}`;
                this.form = {
                    supplier_id: order.supplier_id || '',
                    po_no: order.po_no || '',
                    date: order.date || '',
                };
                this.formItems = (this.itemsByOrder[id] || []).map((item) => ({
                    product_id: item.product_id,
                    qty_ordered: item.qty_ordered,
                }));
                if (this.formItems.length === 0) this.addItem();
                this.formOpen = true;
            },
            closeForm() { this.formOpen = false; },
            addItem() { this.formItems.push({ product_id: '', qty_ordered: 1 }); },
            openDetails(id) { this.selectedOrderId = id; this.detailsOpen = true; },
            closeDetails() { this.detailsOpen = false; this.selectedOrderId = null; },
            selectedOrder() { return this.orders.find((row) => String(row.id) === String(this.selectedOrderId)) || null; },
            selectedItems() { return this.itemsByOrder[this.selectedOrderId] || []; },
            selectedActionOrder() { return this.orders.find((row) => String(row.id) === String(this.actionOrderId)) || null; },
            openVoid(id) { this.actionOrderId = id; this.voidOpen = true; },
            closeVoid() { this.voidOpen = false; this.actionOrderId = null; },
            formatQty(value) { return (Math.round((parseFloat(value) || 0) * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    }
</script>
<?= $this->endSection() ?>
