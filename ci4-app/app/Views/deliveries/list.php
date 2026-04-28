<?php
/**
 * @var array{id: int|string, name: string, payment_term?: int|string|null} $client
 * @var string $fromDate
 * @var string $toDate
 * @var string $drNo
 * @var list<array{id: int|string, client_id?: int|string|null, dr_no?: string|null, date?: string|null, due_date?: string|null, payment_term?: int|string|null, total_amount?: int|float|string|null, balance?: int|float|string|null}> $deliveries
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByDelivery
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByDelivery
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 * @var string $pagerLinks
 * @var int $rowOffset
 * @var array<string, mixed> $deliveryFormData
 * @var array<string, mixed> $quickPayData
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$deliveriesJson = json_encode($deliveries ?? [], $jsonFlags);
$itemsJson = json_encode($itemsByDelivery ?? [], $jsonFlags);
$allocationsJson = json_encode($allocationsByDelivery ?? [], $jsonFlags);
$quickPayActiveReceipt = ! empty($quickPayData['activeReceipt']);
?>

<div x-data="deliveryList()" @close-delivery-form.window="deliveryFormOpen = false">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">Deliveries for <?= esc((string) ($client['name'] ?? '')) ?></h2>
            <p class="mt-1 text-sm muted">Filter deliveries by date range or DR number.</p>
        </div>

        <div class="flex items-center gap-2">
            <?php if (! empty($client['id'])): ?>
                <button class="btn" type="button" @click="openDeliveryForm()">New Delivery</button>
                <a class="btn btn-secondary" href="<?= base_url('ledger?client_id=' . $client['id']) ?>">Ledger</a>
                <a class="btn btn-secondary" href="<?= base_url('payments/client/' . $client['id']) ?>">Collections</a>
                <a class="btn btn-secondary" target="_blank" href="<?= base_url('clients/' . $client['id'] . '/deliveries/print') ?>?from_date=<?= esc($fromDate ?? '') ?>&to_date=<?= esc($toDate ?? '') ?>&dr_no=<?= esc($drNo ?? '') ?>">Print PDF</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('clients') ?>?q=<?= rawurlencode((string) ($client['name'] ?? '')) ?>">Back</a>
        </div>
    </div>

    <form method="get" action="<?= base_url('clients/' . ($client['id'] ?? 0) . '/deliveries') ?>" class="mt-4 grid gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium" for="dr_no">DR Number</label>
            <input
                class="input mt-1"
                id="dr_no"
                name="dr_no"
                value="<?= esc($drNo ?? '') ?>">
        </div>
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
            <a class="btn btn-secondary" href="<?= base_url('clients/' . ($client['id'] ?? 0) . '/deliveries') ?>">Clear</a>
        </div>
    </form>

    <table class="table mt-6">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>DR #</th>
                <th>Due Date</th>
                <th>Term</th>
                <th>Total Amount</th>
                <th>Balance</th>
                <th class="text-right">Collect</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr>
                    <td class="py-3" colspan="8">No deliveries found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($deliveries as $index => $delivery): ?>
                    <tr>
                        <td><?= esc((int) ($rowOffset ?? 0) + $index + 1) ?></td>
                        <td><?= esc((string) $delivery['date']) ?></td>
                        <td>
                            <?php if (! empty($allocationsByDelivery[$delivery['id']])): ?>
                                <button class="btn-link" type="button" @click="openAllocations(<?= (int) $delivery['id'] ?>)">
                                    <?= esc((string) ($delivery['dr_no'] ?? '')) ?>
                                </button>
                            <?php else: ?>
                                <?= esc((string) ($delivery['dr_no'] ?? '')) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($delivery['due_date'] ?? '')) ?></td>
                        <td><?= esc(($delivery['payment_term'] ?? '') !== '' ? $delivery['payment_term'] . ' days' : '') ?></td>
                        <td>
                            <?php if (! empty($itemsByDelivery[$delivery['id']])): ?>
                                <button class="btn-link" type="button" @click="openItems(<?= (int) $delivery['id'] ?>)">
                                    <?= esc(number_format((float) $delivery['total_amount'], 2)) ?>
                                </button>
                            <?php else: ?>
                                <?= esc(number_format((float) $delivery['total_amount'], 2)) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc(number_format((float) $delivery['balance'], 2)) ?></td>
                        <td class="text-right">
                            <button
                                class="btn btn-secondary"
                                type="button"
                                @click="openQuickPay(<?= (int) $delivery['id'] ?>)"
                                <?= ((float) $delivery['balance'] > 0 && $quickPayActiveReceipt) ? '' : 'disabled' ?>>
                                Collect
                            </button>
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
        <div class="card p-4 text-sm">
            <div class="flex justify-between">
                <span>Total Amount</span>
                <span><?= esc(number_format((float) $totalAmount, 2)) ?></span>
            </div>
        </div>
        <div class="card p-4 text-sm">
            <div class="flex justify-between">
                <span>Total Balance</span>
                <span><?= esc(number_format((float) $totalBalance, 2)) ?></span>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="itemsOpen" x-cloak @click.self="closeItems()">
        <div class="modal-panel max-w-lg p-6" @click.stop>
            <h2 class="text-lg font-semibold">Delivery Items</h2>
            <table class="table mt-4">
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
                            <td class="py-3" colspan="4">No items found.</td>
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
            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="font-semibold">Total</span>
                <span x-text="itemsTotal()"></span>
            </div>
            <div class="mt-4">
                <button class="btn" type="button" @click="closeItems()">Close</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="deliveryFormOpen" x-cloak @click.self="deliveryFormOpen = false">
        <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">New Delivery</h2>
                    <p class="mt-1 text-sm muted">Add a delivery receipt for <?= esc((string) ($client['name'] ?? '')) ?>.</p>
                </div>
                <button class="btn btn-secondary" type="button" @click="deliveryFormOpen = false">Close</button>
            </div>
            <?= view('deliveries/form', $deliveryFormData ?? []) ?>
        </div>
    </div>

    <?= view('deliveries/_quick_pay_modal', ['quickPayData' => $quickPayData ?? []]) ?>

    <div class="modal-backdrop" x-show="allocOpen" x-cloak @click.self="closeAllocations()">
        <div class="modal-panel max-w-lg p-6" @click.stop>
            <h2 class="text-lg font-semibold">DR Allocations</h2>
            <table class="table mt-4">
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
                            <td class="py-3" colspan="3">No allocations found.</td>
                        </tr>
                    </template>
                    <template x-for="(allocation, index) in selectedAllocations()" :key="index">
                        <tr>
                            <td x-text="allocation.pr_no"></td>
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
    function deliveryList() {
        return {
            quickPayDeliveries: <?= $deliveriesJson ?>,
            itemsByDelivery: <?= $itemsJson ?>,
            allocationsByDelivery: <?= $allocationsJson ?>,
            itemsOpen: false,
            allocOpen: false,
            deliveryFormOpen: <?= (old('dr_no') || old('items')) && ! old('delivery_id') ? 'true' : 'false' ?>,
            quickPayOpen: <?= old('delivery_id') ? 'true' : 'false' ?>,
            selectedDeliveryId: null,
            quickPayDeliveryId: '<?= esc(old('delivery_id') ?: '') ?>',
            quickPay: {
                date: '<?= esc(old('date') && old('delivery_id') ? old('date') : date('Y-m-d')) ?>',
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
            openDeliveryForm() {
                this.deliveryFormOpen = true;
                this.itemsOpen = false;
                this.allocOpen = false;
                this.quickPayOpen = false;
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
            selectedItems() {
                return this.itemsByDelivery[this.selectedDeliveryId] || [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
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
                this.deliveryFormOpen = false;
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
            }
        };
    }
</script>
<?= $this->endSection() ?>
