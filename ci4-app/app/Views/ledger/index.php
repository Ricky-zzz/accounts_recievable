<?php
/**
 * @var list<array{id: int|string, name: string}> $clients
 * @var array{id: int|string, name: string, address?: string|null, payment_term?: int|string|null}|null $selectedClient
 * @var int $clientId
 * @var string $start
 * @var string $end
 * @var int|float|string $openingBalance
 * @var int|float|string $currentBalance
 * @var list<array<string, int|float|string|null>> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 * @var int $rowOffset
 * @var array<int|string, list<array<string, int|float|string|null>>> $itemsByDelivery
 * @var array<int|string, int> $itemCounts
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByDelivery
 * @var array<int|string, list<array<string, int|float|string|null>>> $allocationsByPayment
 * @var array<int|string, list<array<string, int|float|string|null>>> $otherAccountsByPayment
 * @var array<int|string, array<string, int|float|string|null>> $paymentsById
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$itemsJson = json_encode($itemsByDelivery ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$deliveryAllocJson = json_encode($allocationsByDelivery ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$paymentAllocJson = json_encode($allocationsByPayment ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$paymentOtherJson = json_encode($otherAccountsByPayment ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$paymentsByIdJson = json_encode($paymentsById ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<script>
    // Store data in window before Alpine initializes
    window.ledgerData = {
        itemsByDelivery: <?= $itemsJson ?>,
        allocationsByDelivery: <?= $deliveryAllocJson ?>,
        allocationsByPayment: <?= $paymentAllocJson ?>,
        otherAccountsByPayment: <?= $paymentOtherJson ?>,
        paymentsById: <?= $paymentsByIdJson ?>
    };

    function ledgerItems() {
        return {
            ...soaModalState(),
            itemsByDelivery: window.ledgerData.itemsByDelivery,
            allocationsByDelivery: window.ledgerData.allocationsByDelivery,
            allocationsByPayment: window.ledgerData.allocationsByPayment,
            otherAccountsByPayment: window.ledgerData.otherAccountsByPayment,
            paymentsById: window.ledgerData.paymentsById,
            itemsOpen: false,
            drDetailsOpen: false,
            allocOpen: false,
            allocType: '',
            selectedItemId: null,
            selectedAllocId: null,
            openItems(id) {
                this.selectedItemId = id;
                this.itemsOpen = true;
                this.allocOpen = false;
            },
            closeItems() {
                this.itemsOpen = false;
                this.selectedItemId = null;
            },
            openDrDetails(id) {
                this.selectedItemId = id;
                this.drDetailsOpen = true;
                this.allocOpen = false;
            },
            closeDrDetails() {
                this.drDetailsOpen = false;
                this.selectedItemId = null;
            },
            selectedDrNumber() {
                // Find from items data which has delivery_id
                for (const deliveryId in this.itemsByDelivery) {
                    if (String(deliveryId) === String(this.selectedItemId)) {
                        return this.itemsByDelivery[deliveryId][0]?.dr_no || '';
                    }
                }
                return '';
            },
            selectedItems() {
                return this.itemsByDelivery[this.selectedItemId] || [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            openDeliveryAllocations(id) {
                this.allocType = 'delivery';
                this.selectedAllocId = id;
                this.allocOpen = true;
                this.itemsOpen = false;
            },
            openPaymentAllocations(id) {
                this.allocType = 'payment';
                this.selectedAllocId = id;
                this.allocOpen = true;
                this.itemsOpen = false;
            },
            closeAllocations() {
                this.allocOpen = false;
                this.allocType = '';
                this.selectedAllocId = null;
            },
            selectedAllocations() {
                if (this.allocType === 'delivery') {
                    return this.allocationsByDelivery[this.selectedAllocId] || [];
                }
                if (this.allocType === 'payment') {
                    return this.allocationsByPayment[this.selectedAllocId] || [];
                }
                return [];
            },
            selectedOtherAccounts() {
                return this.otherAccountsByPayment[this.selectedAllocId] || [];
            },
            selectedPayment() {
                return this.paymentsById[this.selectedAllocId] || null;
            },
            selectedAllocatedTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
            },
            selectedArOtherTotal() {
                return this.selectedOtherAccounts()
                    .reduce((sum, item) => sum + (parseFloat(item.ar_others) || 0), 0);
            },
            selectedOtherAccountsBreakdown() {
                return this.selectedOtherAccounts().filter((item) => (parseFloat(item.dr) || 0) > 0 && (item.account_title || '').trim() !== '');
            },
            selectedArOther() {
                return this.selectedOtherAccounts().find((item) => (parseFloat(item.ar_others) || 0) > 0) || null;
            },
            selectedDrItems() {
                return this.itemsByDelivery[this.selectedItemId] || [];
            },
            drItemsTotal() {
                return this.selectedDrItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            selectedDrAllocations() {
                return this.allocationsByDelivery[this.selectedItemId] || [];
            },
            drAllocationsTotal() {
                return this.selectedDrAllocations()
                    .reduce((sum, alloc) => sum + (parseFloat(alloc.amount) || 0), 0)
                    .toFixed(2);
            },
            otherAccountsTotal() {
                return this.selectedOtherAccounts()
                    .reduce((sum, item) => sum + (parseFloat(item.dr) || parseFloat(item.ar_others) || 0), 0)
                    .toFixed(2);
            },
            allocTitle() {
                return this.allocType === 'delivery' ? 'DR Allocations' : 'PR Summary';
            },
            selectedPrNumber() {
                const payment = this.paymentsById[String(this.selectedAllocId)];
                return payment ? payment.pr_no : '';
            }
        };
    }
</script>

<div x-data="ledgerItems()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-col">
            <h1 class="text-xl font-semibold">
                Ledger<?= $selectedClient ? ' for ' . esc((string) ($selectedClient['name'] ?? '')) : '' ?>
            </h1>
            <p class="mt-1 text-sm muted">Shows overall balance with optional date range.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($selectedClient): ?>
                <a class="btn btn-secondary" href="<?= base_url('clients/' . $clientId . '/deliveries') ?>">Deliveries</a>
                <a class="btn btn-secondary" href="<?= base_url('payments/client/' . $clientId) ?>">Collections</a>
                <button class="btn btn-secondary" type="button" @click="openSoaModal(<?= (int) $clientId ?>, '<?= esc((string) ($selectedClient['name'] ?? ''), 'js') ?>', '<?= esc((string) ($selectedClient['payment_term'] ?? ''), 'js') ?>')">SOA</button>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('clients') ?>?q=<?= rawurlencode((string) ($selectedClient['name'] ?? '')) ?>">Back</a>
        </div>
    </div>

    <form class="filter-card mt-6 rounded border border-gray-200 p-4" method="get" action="<?= base_url('ledger') ?>" x-data>
        <div class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="client_id" value="<?= esc((string) $clientId) ?>">
        <div>
            <label class="block text-sm font-medium" for="start">Start Date</label>
            <input class="input mt-1" id="start" name="start" type="date" value="<?= esc($start) ?>" @change="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="end">End Date</label>
            <input class="input mt-1" id="end" name="end" type="date" value="<?= esc($end) ?>" @change="$el.form.requestSubmit()">
        </div>
        <div class="flex items-end gap-2">
            <?php if ($selectedClient): ?>
                <a class="btn btn-secondary" target="_blank" href="<?= base_url('ledger/print') ?>?client_id=<?= esc((string) $clientId) ?>&start=<?= esc($start) ?>&end=<?= esc($end) ?>">Print</a>
                <a class="btn btn-secondary" href="<?= base_url('ledger') ?>?client_id=<?= esc((string) $clientId) ?>">Clear</a>
            <?php endif; ?>
        </div>
        </div>
    </form>

    <?php if ($selectedClient): ?>


        <table class="table mt-6">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>DR#</th>
                    <th>PR#</th>
                    <th>Account Title</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Amount</th>
                    <th>Collection</th>
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
                        <td class="py-3" colspan="11">No deliveries in range.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?= esc((string) ((int) ($rowOffset ?? 0) + $index + 1)) ?></td>
                            <td><?= esc((string) $row['entry_date']) ?></td>
                            <td>
                                <?php if (! empty($row['delivery_id']) && (! empty($itemsByDelivery[$row['delivery_id']]) || ! empty($allocationsByDelivery[$row['delivery_id']]))) : ?>
                                    <button class="btn-link" type="button" @click="openDrDetails(<?= (int) $row['delivery_id'] ?>)">
                                        <?= esc((string) ($row['dr_no'] ?? '')) ?>
                                    </button>
                                <?php else: ?>
                                    <?= esc((string) ($row['dr_no'] ?? '')) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (! empty($row['payment_id']) && ! empty($allocationsByPayment[$row['payment_id']])): ?>
                                    <button class="btn-link" type="button" @click="openPaymentAllocations(<?= (int) $row['payment_id'] ?>)">
                                        <?= esc((string) ($row['pr_no'] ?? '')) ?>
                                    </button>
                                <?php else: ?>
                                    <?= esc((string) ($row['pr_no'] ?? '')) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= esc((string) ($row['account_title'] ?? '')) ?></td>
                            <td>
                                <?= esc((string) ($row['qty'] ?? '')) ?>
                                <?php if (! empty($row['delivery_id']) && ($itemCounts[$row['delivery_id']] ?? 0) > 1): ?>
                                    <span class="muted">+</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc((string) ($row['price'] ?? '')) ?></td>
                            <td><?= esc(number_format((float) $row['amount'], 2)) ?></td>
                            <td>
                                <?php $collection = (float) ($row['collection'] ?? 0); ?>
                                <?= $collection > 0 ? esc(number_format($collection, 2)) : '' ?>
                            </td>
                            <td>
                                <?php $otherAccounts = (float) ($row['other_accounts'] ?? 0); ?>
                                <?= $otherAccounts > 0 ? esc(number_format($otherAccounts, 2)) : '' ?>
                            </td>
                            <td><?= esc(number_format((float) $row['balance'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="mt-4 flex items-center justify-between gap-4 text-sm muted">
                <div>
                    Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (($currentPage ?? 1) > 1): ?>
                        <a class="btn btn-secondary" href="<?= base_url('ledger?' . http_build_query(['client_id' => $clientId, 'start' => $start, 'end' => $end, 'page' => $currentPage - 1])) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                        <a class="btn btn-secondary" href="<?= base_url('ledger?' . http_build_query(['client_id' => $clientId, 'start' => $start, 'end' => $end, 'page' => $currentPage + 1])) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="card p-4 total-highlight">
                <div class="flex justify-between">
                    <span>Current Balance</span>
                    <span><?= esc(number_format((float) ($currentBalance ?? $openingBalance ?? 0), 2)) ?></span>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
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
                            <template x-if="selectedDrItems().length === 0">
                                <tr>
                                    <td class="py-3 text-center" colspan="4">No items found.</td>
                                </tr>
                            </template>
                            <template x-for="item in selectedDrItems()" :key="item.id">
                                <tr>
                                    <td x-text="item.product_name"></td>
                                    <td x-text="item.qty"></td>
                                    <td x-text="Number(item.unit_price).toFixed(2)"></td>
                                    <td x-text="Number(item.line_total).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="mt-2 text-sm font-semibold" x-show="selectedDrItems().length > 0">
                        Total: <span x-text="drItemsTotal()"></span>
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
                            <template x-if="selectedDrAllocations().length === 0">
                                <tr>
                                    <td class="py-3 text-center" colspan="3">No allocations found.</td>
                                </tr>
                            </template>
                            <template x-for="(alloc, index) in selectedDrAllocations()" :key="index">
                                <tr>
                                    <td x-text="alloc.pr_no"></td>
                                    <td x-text="alloc.date"></td>
                                    <td x-text="Number(alloc.amount).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="mt-2 text-sm font-semibold" x-show="selectedDrAllocations().length > 0">
                        Total: <span x-text="drAllocationsTotal()"></span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button class="btn" type="button" @click="closeDrDetails()">Close</button>
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

    <div class="modal-backdrop" x-show="allocOpen" x-cloak @click.self="closeAllocations()">
        <div class="modal-panel max-w-lg p-6" @click.stop>
            <h2 class="text-lg font-semibold">
                <span x-text="allocType === 'delivery' ? 'DR Allocations' : 'PR Summary for: ' + (selectedPayment() ? selectedPayment().pr_no : '')"></span>
            </h2>
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th x-text="allocType === 'delivery' ? 'PR#' : 'DR#'"></th>
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
                            <td x-text="allocType === 'delivery' ? allocation.pr_no : allocation.dr_no"></td>
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
            <template x-if="allocType === 'payment'">
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
            </template>
            <div class="mt-4">
                <button class="btn" type="button" @click="closeAllocations()">Close</button>
            </div>
        </div>
    </div>
    <?= view('clients/_soa_modal') ?>
</div>

<?= $this->endSection() ?>
