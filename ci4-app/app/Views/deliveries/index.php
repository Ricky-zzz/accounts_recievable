<?php
/**
 * @var string $fromDate
 * @var string $toDate
 * @var string $drNo
 * @var list<array{id: int|string, client_id?: int|string|null, dr_no?: string|null, date?: string|null, due_date?: string|null, payment_term?: int|string|null, client_name?: string|null, total_amount?: int|float|string|null, allocated_amount?: int|float|string|null, balance?: int|float|string|null}> $deliveries
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByDelivery
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByDelivery
 * @var array<int|string, list<array<string, int|float|string|null>>> $historiesByDelivery
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 * @var string $pagerLinks
 * @var int $rowOffset
 * @var array<string, mixed> $quickPayData
 * @var array<string, mixed> $deliveryActionData
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$deliveriesJson = json_encode($deliveries ?? [], $jsonFlags);
$itemsJson = json_encode($itemsByDelivery ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByDelivery ?? [], $jsonFlags);
$historiesJson = json_encode($historiesByDelivery ?? [], $jsonFlags);
$productsJson = json_encode($deliveryActionData['products'] ?? [], $jsonFlags);
?>

<div x-data="deliveryList()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">Deliveries</h2>
            <p class="mt-1 text-sm muted">Filter deliveries by date range or DR number.</p>
        </div>
        <a class="btn btn-secondary" target="_blank" href="<?= base_url('deliveries/print') ?>?from_date=<?= esc($fromDate ?? '') ?>&to_date=<?= esc($toDate ?? '') ?>&dr_no=<?= esc($drNo ?? '') ?>">Print</a>
    </div>

    <form method="get" action="<?= base_url('deliveries') ?>" class="filter-card mt-4 rounded border border-gray-200 p-4" x-data>
        <div class="grid gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium" for="dr_no">DR Number</label>
            <input
                class="input mt-1"
                id="dr_no"
                name="dr_no"
                value="<?= esc($drNo ?? '') ?>"
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
            <a class="btn btn-secondary" href="<?= base_url('deliveries') ?>">Clear</a>
        </div>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>DR #</th>
                <th>Client</th>
                <th>Due Date</th>
                <th>Term</th>
                <th class="text-right" style="text-align: right;">Total Amount</th>
                <th class="text-right" style="text-align: right;">Collected</th>
                <th class="text-right" style="text-align: right;">Balance</th>
                <th class="text-center" style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr>
                    <td class="py-3" colspan="10">No deliveries found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($deliveries as $index => $delivery): ?>
                    <?php
                        $deliveryBalance = (float) ($delivery['balance'] ?? 0);
                        $deliveryAllocated = (float) ($delivery['allocated_amount'] ?? 0);
                        $canCollectDelivery = $deliveryBalance > 0;
                        $canEditDelivery = $deliveryAllocated <= 0;
                        $canVoidDelivery = $deliveryAllocated <= 0 && $deliveryBalance > 0;
                    ?>
                    <tr>
                        <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                        <td><?= esc((string) $delivery['date']) ?></td>
                        <td>
                            <?php if (! empty($itemsByDelivery[$delivery['id']]) || ! empty($allocationsByDelivery[$delivery['id']])): ?>
                                <button class="btn-link" type="button" @click="openDrDetails(<?= (int) $delivery['id'] ?>)">
                                    <?= esc((string) ($delivery['dr_no'] ?? '')) ?>
                                </button>
                            <?php else: ?>
                                <?= esc((string) ($delivery['dr_no'] ?? '')) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($delivery['client_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($delivery['due_date'] ?? '')) ?></td>
                        <td><?= esc(($delivery['payment_term'] ?? '') !== '' ? $delivery['payment_term'] . ' days' : '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) $delivery['total_amount'], 2)) ?></td>
                        <td class="text-right"><?= esc(number_format($deliveryAllocated, 2)) ?></td>
                        <td class="text-right"><?= esc(number_format($deliveryBalance, 2)) ?></td>
                        <td class="text-center">
                            <div class="flex flex-wrap justify-center gap-2">
                                <button class="btn btn-secondary btn-strong" type="button" @click="openQuickPay(<?= (int) $delivery['id'] ?>)" <?= $canCollectDelivery ? '' : 'disabled' ?>>Collect</button>
                                <button class="btn btn-secondary" type="button" @click="openSoaModal(<?= (int) ($delivery['client_id'] ?? 0) ?>, '<?= esc((string) ($delivery['client_name'] ?? ''), 'js') ?>', '<?= esc((string) ($delivery['client_payment_term'] ?? $delivery['payment_term'] ?? ''), 'js') ?>')" <?= empty($delivery['client_id']) ? 'disabled' : '' ?>>SOA</button>
                                <button class="btn btn-secondary" type="button" @click="openEdit(<?= (int) $delivery['id'] ?>)" <?= $canEditDelivery ? '' : 'disabled' ?>>Edit</button>
                                <button class="btn btn-secondary" type="button" @click="openVoid(<?= (int) $delivery['id'] ?>)" <?= $canVoidDelivery ? '' : 'disabled' ?>>Void</button>
                                <button class="btn btn-secondary" type="button" @click="openHistory(<?= (int) $delivery['id'] ?>)">History</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (! empty($pagerLinks ?? '')): ?>
        <div class="mt-4">
            <?= $pagerLinks ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="card p-4 total-highlight">
            <div class="flex justify-between">
                <span>Total Amount</span>
                <span><?= esc(number_format((float) $totalAmount, 2)) ?></span>
            </div>
        </div>
        <div class="card p-4 total-highlight">
            <div class="flex justify-between">
                <span>Total Balance</span>
                <span><?= esc(number_format((float) $totalBalance, 2)) ?></span>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="drDetailsOpen" x-cloak @click.self="closeDrDetails()">
        <div class="modal-panel max-w-4xl p-6" @click.stop>
            <div class="mb-4 border-b pb-4">
                <h2 class="text-lg font-semibold">Details for DR#: <span x-text="selectedDrNumber()"></span></h2>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="mb-3 font-semibold">Delivery Items</h3>
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
                                    <td class="py-3 text-center" colspan="4">No items found.</td>
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
                    <div class="mt-2 text-sm font-semibold" x-show="selectedItems().length > 0">
                        Total: <span x-text="itemsTotal()"></span>
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold">DR Allocations</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>PR #</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="selectedAllocations().length === 0">
                                <tr>
                                    <td class="py-3 text-center" colspan="3">No allocations found.</td>
                                </tr>
                            </template>
                            <template x-for="(alloc, index) in selectedAllocations()" :key="index">
                                <tr>
                                    <td x-text="alloc.pr_no"></td>
                                    <td x-text="alloc.date"></td>
                                    <td x-text="Number(alloc.amount).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="mt-2 text-sm font-semibold" x-show="selectedAllocations().length > 0">
                        Total: <span x-text="allocationsTotal()"></span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button class="btn" type="button" @click="closeDrDetails()">Close</button>
            </div>
        </div>
    </div>

    <?= view('deliveries/_quick_pay_modal', ['quickPayData' => $quickPayData ?? []]) ?>
    <?= view('deliveries/_action_modals', ['deliveryActionData' => $deliveryActionData ?? []]) ?>
    <?= view('clients/_soa_modal') ?>
</div>

<script>
    function deliveryList() {
        return {
            ...soaModalState(),
            quickPayDeliveries: <?= $deliveriesJson ?>,
            itemsByDelivery: <?= $itemsJson ?>,
            allocationsByDelivery: <?= $allocationsJson ?>,
            historiesByDelivery: <?= $historiesJson ?>,
            products: <?= $productsJson ?>,
            itemsOpen: false,
            drDetailsOpen: false,
            allocOpen: false,
            editOpen: false,
            voidOpen: false,
            historyOpen: false,
            quickPayOpen: <?= old('delivery_id') ? 'true' : 'false' ?>,
            selectedDeliveryId: null,
            actionDeliveryId: null,
            editDelivery: {
                dr_no: '',
                date: '',
                payment_term: '',
                due_date: '',
            },
            editItems: [],
            quickPayDeliveryId: '<?= esc(old('delivery_id') ?: '') ?>',
            quickPay: {
                date: '<?= esc(old('date') ?: date('Y-m-d')) ?>',
                method: '<?= esc(old('method') ?: 'cash') ?>',
                amountReceived: '<?= esc(old('amount_received')) ?>',
                depositBankId: '<?= esc(old('deposit_bank_id')) ?>',
                payerBank: '<?= esc(old('payer_bank')) ?>',
                checkNo: '<?= esc(old('check_no')) ?>',
                allocationAmount: '<?= esc(old('allocation_amount')) ?>',
                salesDiscount: '<?= esc(old('sales_discount')) ?>',
                deliveryCharges: '<?= esc(old('delivery_charges')) ?>',
                taxes: '<?= esc(old('taxes')) ?>',
                commissions: '<?= esc(old('commissions')) ?>',
                arOtherDescription: '<?= esc(old('ar_other_description')) ?>',
                arOtherAmount: '<?= esc(old('ar_other_amount')) ?>',
            },
            normalizeAmount(value) {
                return Math.round((parseFloat(value) || 0) * 100) / 100;
            },
            formatAmount(value) {
                return this.normalizeAmount(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
            formatInputAmount(value) {
                return this.normalizeAmount(value).toFixed(2);
            },
            openItems(id) {
                this.selectedDeliveryId = id;
                this.itemsOpen = true;
                this.allocOpen = false;
                this.quickPayOpen = false;
            },
            closeItems() {
                this.itemsOpen = false;
                this.selectedDeliveryId = null;
            },
            openDrDetails(id) {
                this.selectedDeliveryId = id;
                this.drDetailsOpen = true;
                this.quickPayOpen = false;
            },
            closeDrDetails() {
                this.drDetailsOpen = false;
                this.selectedDeliveryId = null;
            },
            selectedDrNumber() {
                const delivery = this.quickPayDeliveries.find((d) => String(d.id) === String(this.selectedDeliveryId));
                return delivery ? delivery.dr_no : '';
            },
            selectedItems() {
                return this.itemsByDelivery[this.selectedDeliveryId] || [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            allocationsTotal() {
                return this.selectedAllocations()
                    .reduce((sum, alloc) => sum + (parseFloat(alloc.amount) || 0), 0)
                    .toFixed(2);
            },
            openAllocations(id) {
                this.selectedDeliveryId = id;
                this.allocOpen = true;
                this.itemsOpen = false;
                this.quickPayOpen = false;
            },
            closeAllocations() {
                this.allocOpen = false;
                this.selectedDeliveryId = null;
            },
            selectedAllocations() {
                return this.allocationsByDelivery[this.selectedDeliveryId] || [];
            },
            allocTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
                    .toFixed(2);
            },
            selectedQuickPayDelivery() {
                return this.quickPayDeliveries.find((delivery) => String(delivery.id) === String(this.quickPayDeliveryId)) || null;
            },
            openQuickPay(id) {
                this.quickPayDeliveryId = id;
                const delivery = this.selectedQuickPayDelivery();
                if (delivery) {
                    this.quickPay.amountReceived = this.formatInputAmount(delivery.balance);
                    this.quickPay.allocationAmount = this.formatInputAmount(delivery.balance);
                }
                this.quickPayOpen = true;
                this.itemsOpen = false;
                this.allocOpen = false;
            },
            closeQuickPay() {
                this.quickPayOpen = false;
                this.quickPayDeliveryId = '';
            },
            quickPayFixedAccountsTotal() {
                return [
                    this.quickPay.salesDiscount,
                    this.quickPay.deliveryCharges,
                    this.quickPay.taxes,
                    this.quickPay.commissions
                ].reduce((sum, value) => sum + (parseFloat(value) || 0), 0);
            },
            quickPayBalanceAmount() {
                return (parseFloat(this.quickPay.amountReceived) || 0)
                    + this.quickPayFixedAccountsTotal()
                    - (parseFloat(this.quickPay.allocationAmount) || 0)
                    - (parseFloat(this.quickPay.arOtherAmount) || 0);
            },
            selectedActionDelivery() {
                return this.quickPayDeliveries.find((delivery) => String(delivery.id) === String(this.actionDeliveryId)) || null;
            },
            openEdit(id) {
                this.actionDeliveryId = id;
                const delivery = this.selectedActionDelivery();
                if (!delivery) {
                    return;
                }
                this.editDelivery = {
                    dr_no: delivery.dr_no || '',
                    date: delivery.date || '',
                    payment_term: delivery.payment_term || '',
                    due_date: delivery.due_date || '',
                };
                this.editItems = (this.itemsByDelivery[id] || []).map((item) => ({
                    product_id: item.product_id,
                    qty: item.qty,
                    unit_price: item.unit_price,
                    line_total: this.formatInputAmount(item.line_total)
                }));
                if (this.editItems.length === 0) {
                    this.addEditItem();
                }
                this.editOpen = true;
                this.itemsOpen = false;
                this.allocOpen = false;
                this.quickPayOpen = false;
            },
            closeEdit() {
                this.editOpen = false;
                this.actionDeliveryId = null;
            },
            recomputeEditDueDate() {
                if (!this.editDelivery.date) {
                    this.editDelivery.due_date = '';
                    return;
                }

                const term = parseInt(this.editDelivery.payment_term, 10);
                const days = Number.isFinite(term) && term >= 0 ? term : 0;
                const date = new Date(this.editDelivery.date + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    this.editDelivery.due_date = '';
                    return;
                }

                date.setDate(date.getDate() + days);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                this.editDelivery.due_date = `${year}-${month}-${day}`;
            },
            addEditItem() {
                this.editItems.push({
                    product_id: '',
                    qty: 1,
                    unit_price: '',
                    line_total: '0.00'
                });
            },
            removeEditItem(index) {
                this.editItems.splice(index, 1);
            },
            selectEditProduct(index) {
                const item = this.editItems[index];
                const product = this.products.find((row) => String(row.id) === String(item.product_id));
                item.unit_price = product ? product.unit_price : '';
                this.updateEditLine(index);
            },
            updateEditLine(index) {
                const item = this.editItems[index];
                const qty = parseFloat(item.qty) || 0;
                const price = parseFloat(item.unit_price) || 0;
                item.line_total = (qty * price).toFixed(2);
            },
            editTotal() {
                return this.editItems
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            openVoid(id) {
                this.actionDeliveryId = id;
                this.voidOpen = true;
                this.itemsOpen = false;
                this.allocOpen = false;
                this.quickPayOpen = false;
            },
            closeVoid() {
                this.voidOpen = false;
                this.actionDeliveryId = null;
            },
            openHistory(id) {
                this.actionDeliveryId = id;
                this.historyOpen = true;
                this.itemsOpen = false;
                this.allocOpen = false;
                this.quickPayOpen = false;
            },
            closeHistory() {
                this.historyOpen = false;
                this.actionDeliveryId = null;
            },
            selectedHistories() {
                return this.historiesByDelivery[this.actionDeliveryId] || [];
            },
            historyDelivery(value) {
                if (!value) {
                    return {};
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    return {};
                }
            },
            historyItems(value) {
                if (!value) {
                    return [];
                }
                try {
                    const items = JSON.parse(value);
                    return Array.isArray(items) ? items : [];
                } catch (error) {
                    return [];
                }
            }
        };
    }
</script>
<?= $this->endSection() ?>
