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
 * @var array<int|string, int> $itemCounts
 * @var float $forwardedBalance
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<script>
    function ledgerItems() {
        return {
            ...soaModalState(),
            forwardedBalance: <?= json_encode((float) ($forwardedBalance ?? 0)) ?>,
            forwardBalanceOpen: false,
            forwardBalanceAmount: <?= json_encode((float) ($forwardedBalance ?? 0)) ?>,
            detailUrls: {
                delivery: '<?= base_url('ajax/deliveries') ?>',
                payment: '<?= base_url('ajax/payments') ?>',
            },
            deliveryDetailsById: {},
            paymentDetailsById: {},
            itemsOpen: false,
            drDetailsOpen: false,
            allocOpen: false,
            allocType: '',
            selectedItemId: null,
            selectedAllocId: null,
            selectedDrLabel: '',
            selectedPrLabel: '',
            detailLoading: false,
            detailError: '',
            openForwardBalance() {
                this.forwardBalanceAmount = this.forwardedBalance;
                this.forwardBalanceOpen = true;
            },
            closeForwardBalance() {
                this.forwardBalanceOpen = false;
            },
            async openItems(id) {
                this.selectedItemId = id;
                this.detailError = '';
                this.itemsOpen = true;
                this.allocOpen = false;
                this.drDetailsOpen = false;
                await this.loadDeliveryDetails(id);
            },
            closeItems() {
                this.itemsOpen = false;
                this.selectedItemId = null;
            },
            async openDrDetails(id, drNo = '') {
                this.selectedItemId = id;
                this.selectedDrLabel = drNo;
                this.detailError = '';
                this.drDetailsOpen = true;
                this.itemsOpen = false;
                this.allocOpen = false;
                await this.loadDeliveryDetails(id);
            },
            closeDrDetails() {
                this.drDetailsOpen = false;
                this.selectedItemId = null;
            },
            deliveryDetails() {
                return this.deliveryDetailsById[this.selectedItemId] || null;
            },
            selectedDrNumber() {
                const detail = this.deliveryDetails();
                return detail && detail.delivery ? detail.delivery.dr_no : this.selectedDrLabel;
            },
            selectedItems() {
                const detail = this.deliveryDetails();
                return detail ? (detail.items || []) : [];
            },
            itemsTotal() {
                return this.selectedItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            async openDeliveryAllocations(id) {
                this.allocType = 'delivery';
                this.selectedAllocId = id;
                this.detailError = '';
                this.allocOpen = true;
                this.itemsOpen = false;
                this.drDetailsOpen = false;
                await this.loadDeliveryDetails(id);
            },
            async openPaymentAllocations(id, prNo = '') {
                this.allocType = 'payment';
                this.selectedAllocId = id;
                this.selectedPrLabel = prNo;
                this.detailError = '';
                this.allocOpen = true;
                this.itemsOpen = false;
                this.drDetailsOpen = false;
                await this.loadPaymentDetails(id);
            },
            closeAllocations() {
                this.allocOpen = false;
                this.allocType = '';
                this.selectedAllocId = null;
            },
            paymentDetails() {
                return this.paymentDetailsById[this.selectedAllocId] || null;
            },
            selectedAllocations() {
                if (this.allocType === 'delivery') {
                    const detail = this.deliveryDetailsById[this.selectedAllocId] || null;
                    return detail ? (detail.allocations || []) : [];
                }
                if (this.allocType === 'payment') {
                    const detail = this.paymentDetails();
                    return detail ? (detail.allocations || []) : [];
                }
                return [];
            },
            selectedOtherAccounts() {
                const detail = this.paymentDetails();
                return detail ? (detail.other_accounts || []) : [];
            },
            selectedPayment() {
                const detail = this.paymentDetails();
                return detail ? detail.payment : null;
            },
            selectedAllocatedTotal() {
                return this.selectedAllocations()
                    .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
            },
            allocTotal() {
                return this.selectedAllocatedTotal().toFixed(2);
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
                const detail = this.deliveryDetails();
                return detail ? (detail.items || []) : [];
            },
            drItemsTotal() {
                return this.selectedDrItems()
                    .reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0)
                    .toFixed(2);
            },
            selectedDrAllocations() {
                const detail = this.deliveryDetails();
                return detail ? (detail.allocations || []) : [];
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
                const payment = this.selectedPayment();
                return payment ? payment.pr_no : this.selectedPrLabel;
            },
            async loadDeliveryDetails(id) {
                await this.loadDetails(this.detailUrls.delivery, id, this.deliveryDetailsById);
            },
            async loadPaymentDetails(id) {
                await this.loadDetails(this.detailUrls.payment, id, this.paymentDetailsById);
            },
            async loadDetails(baseUrl, id, cache) {
                if (!id || cache[id]) {
                    return;
                }

                this.detailLoading = true;
                this.detailError = '';
                try {
                    const response = await fetch(baseUrl + '/' + id, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Unable to load details.');
                    }
                    cache[id] = data;
                } catch (error) {
                    this.detailError = error.message || 'Unable to load details.';
                } finally {
                    this.detailLoading = false;
                }
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
                <button class="btn btn-secondary" type="button" @click="openForwardBalance()">Forward Balance</button>
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
                                <?php if (! empty($row['delivery_id'])) : ?>
                                    <button class="btn-link" type="button" @click="openDrDetails(<?= (int) $row['delivery_id'] ?>, '<?= esc((string) ($row['dr_no'] ?? ''), 'js') ?>')">
                                        <?= esc((string) ($row['dr_no'] ?? '')) ?>
                                    </button>
                                <?php else: ?>
                                    <?= esc((string) ($row['dr_no'] ?? '')) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (! empty($row['payment_id'])): ?>
                                    <button class="btn-link" type="button" @click="openPaymentAllocations(<?= (int) $row['payment_id'] ?>, '<?= esc((string) ($row['pr_no'] ?? ''), 'js') ?>')">
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
    <div class="modal-backdrop" x-show="forwardBalanceOpen" x-cloak @click.self="closeForwardBalance()">
        <div class="modal-panel max-w-lg p-6" @click.stop>
            <h2 class="text-lg font-semibold">Forward Balance</h2>
            <p class="mt-1 text-sm muted">Update the starting balance used for this ledger.</p>
            <form class="mt-4 space-y-4" method="post" action="<?= base_url('ledger/forward-balance') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="client_id" value="<?= esc((string) $clientId) ?>">
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
    <div class="modal-backdrop" x-show="drDetailsOpen" x-cloak @click.self="closeDrDetails()">
        <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
            <div class="mb-4 border-b pb-4">
                <h2 class="text-lg font-semibold">DR Details: <span x-text="selectedDrNumber()"></span></h2>
            </div>
            <div class="mb-4 text-sm muted" x-show="detailLoading">Loading details...</div>
            <div class="mb-4 text-sm text-red-600" x-show="detailError" x-text="detailError"></div>

            <div class="modal-split">
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
                            <template x-if="detailLoading">
                                <tr>
                                    <td class="py-3 text-center" colspan="4">Loading...</td>
                                </tr>
                            </template>
                            <template x-if="!detailLoading && selectedDrItems().length === 0">
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
                            <template x-if="detailLoading">
                                <tr>
                                    <td class="py-3 text-center" colspan="3">Loading...</td>
                                </tr>
                            </template>
                            <template x-if="!detailLoading && selectedDrAllocations().length === 0">
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
            <div class="mt-3 text-sm muted" x-show="detailLoading">Loading details...</div>
            <div class="mt-3 text-sm text-red-600" x-show="detailError" x-text="detailError"></div>
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
                    <template x-if="detailLoading">
                        <tr>
                            <td class="py-3" colspan="4">Loading...</td>
                        </tr>
                    </template>
                    <template x-if="!detailLoading && selectedItems().length === 0">
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
                <span x-text="allocType === 'delivery' ? 'DR Allocations' : 'PR Summary for: ' + selectedPrNumber()"></span>
            </h2>
            <div class="mt-3 text-sm muted" x-show="detailLoading">Loading details...</div>
            <div class="mt-3 text-sm text-red-600" x-show="detailError" x-text="detailError"></div>
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th x-text="allocType === 'delivery' ? 'PR#' : 'DR#'"></th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="detailLoading">
                        <tr>
                            <td class="py-3" colspan="3">Loading...</td>
                        </tr>
                    </template>
                    <template x-if="!detailLoading && selectedAllocations().length === 0">
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