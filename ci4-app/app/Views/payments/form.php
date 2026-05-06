<?php
/**
 * @var array{id: int|string, name: string} $client
 * @var array{id?: int|string, name?: string|null}|null $assignedUser
 * @var int|null $activeReceipt
 * @var int|null $rangeEnd
 * @var list<array{id: int|string, bank_name: string, account_name?: string|null, bank_number?: string|null}> $banks
 * @var list<array{delivery_id: int|string, dr_no?: string|null, date?: string|null, due_date?: string|null, total_amount?: int|float|string|null, allocated_amount?: int|float|string|null, balance?: int|float|string|null}> $unpaidDeliveries
 * @var string $unpaidPagerLinks
 * @var list<array{payment_id: int|string, client_id: int|string, pr_no?: int|string|null, date?: string|null, amount_received?: int|float|string|null, amount_allocated?: int|float|string|null, balance?: int|float|string|null}> $advanceCollections
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$deliveriesJson = json_encode($unpaidDeliveries ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$advanceCollectionsJson = json_encode($advanceCollections ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$collectorLabel = trim((string) (($assignedUser['name'] ?? '') . ' (' . ($assignedUser['username'] ?? '-') . ')'));
?>

<div x-data="paymentForm()" data-deliveries="<?= esc($deliveriesJson, 'attr') ?>" data-advances="<?= esc($advanceCollectionsJson, 'attr') ?>" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Payment for <?= esc((string) $client['name']) ?></h1>
            <p class="mt-1 text-sm muted">Allocate receipts and commit when ready.</p>
        </div>

        <a class="btn btn-secondary" href="<?= base_url('payments/client/' . $client['id']) ?>">Back</a>
    </div>

    <?php if (! $activeReceipt): ?>
        <div class="card p-4" x-show="!isApplyingAdvance()" x-cloak>
            <p class="text-sm text-red-700">No active receipt range is assigned to your user yet. Ask admin to assign a range before posting new receipts.</p>
        </div>
    <?php endif; ?>

    <form class="space-y-6" method="post" action="<?= base_url('payments') ?>" :action="isApplyingAdvance() ? '<?= base_url('payments/apply-advance') ?>' : '<?= base_url('payments') ?>'" x-on:submit.prevent="submitForm($event)">
        <?= csrf_field() ?>
        <input type="hidden" name="client_id" value="<?= esc((string) $client['id']) ?>">
        <input type="hidden" name="advance_payment_id" :value="selectedAdvancePaymentId">

        <div class="card p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold">Advance Collections</h2>
                    <p class="mt-1 text-sm muted" x-text="advanceCollections.length ? 'Choose a previous PR with remaining advance.' : 'No advance collections available for this client.'"></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-secondary" type="button" @click="advanceModalOpen = true" :disabled="advanceCollections.length === 0">See Advance Collections</button>
                    <button class="btn btn-secondary" type="button" @click="clearAdvance()" x-show="isApplyingAdvance()" x-cloak>Clear Advance</button>
                </div>
            </div>
            <div class="mt-4 rounded border border-gray-200 p-3 text-sm" x-show="isApplyingAdvance()" x-cloak>
                <div class="grid gap-3 sm:grid-cols-4">
                    <div>
                        <p class="muted">Using PR</p>
                        <p class="font-semibold" x-text="selectedAdvance() ? selectedAdvance().pr_no : ''"></p>
                    </div>
                    <div>
                        <p class="muted">Date</p>
                        <p class="font-semibold" x-text="selectedAdvance() ? selectedAdvance().date : ''"></p>
                    </div>
                    <div>
                        <p class="muted">Advance Balance</p>
                        <p class="font-semibold" x-text="formatAmount(selectedAdvance() ? selectedAdvance().balance : 0)"></p>
                    </div>
                    <div>
                        <p class="muted">After Allocations</p>
                        <p class="font-semibold" x-text="formatAmount(advanceRemainingAfterAllocation())"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium">Collector</label>
                    <input class="input mt-1" type="text" value="<?= esc($collectorLabel) ?>" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="pr_no">Active Receipt</label>
                    <input class="input mt-1" id="pr_no" type="text" :value="isApplyingAdvance() && selectedAdvance() ? 'PR #' + selectedAdvance().pr_no : '<?= $activeReceipt ? esc((string) $activeReceipt, 'js') : 'Not assigned' ?>'" readonly>
                    <p class="mt-1 text-xs muted" x-show="!isApplyingAdvance() && hasActiveReceipt && rangeEnd" x-cloak>Range end: <?= esc((string) ($rangeEnd ?? '')) ?></p>
                    <p class="mt-1 text-xs muted" x-show="isApplyingAdvance()" x-cloak>Allocating against an existing PR. No new receipt number will be used.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="date">Date</label>
                    <input class="input mt-1" id="date" name="date" type="date" value="<?= esc(old('date') ?: date('Y-m-d')) ?>" :disabled="isApplyingAdvance()" required>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium" for="method">Payment Method</label>
                    <select class="input mt-1" id="method" name="method" x-model="method" :disabled="isApplyingAdvance()" required>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="check">Check</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="amount_received">Amount Received</label>
                    <input class="input mt-1" id="amount_received" name="amount_received" type="number" step="0.01" min="0" x-model="amountReceived" :readonly="isApplyingAdvance()" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="deposit_bank_id">Deposit Bank</label>
                    <select class="input mt-1" id="deposit_bank_id" name="deposit_bank_id" :disabled="isApplyingAdvance()" required>
                        <option value="">Select bank</option>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?= esc((string) $bank['id']) ?>" <?= (string) old('deposit_bank_id') === (string) $bank['id'] ? 'selected' : '' ?>><?= esc((string) $bank['bank_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div x-show="!isApplyingAdvance() && (method === 'bank' || method === 'check')" x-cloak>
                    <label class="block text-sm font-medium" for="payer_bank">Bank of Payer</label>
                    <input class="input mt-1" id="payer_bank" name="payer_bank" type="text" value="<?= esc(old('payer_bank')) ?>" :disabled="method === 'cash'">
                </div>
                <div x-show="!isApplyingAdvance() && method === 'check'" x-cloak>
                    <label class="block text-sm font-medium" for="check_no">Check Number</label>
                    <input class="input mt-1" id="check_no" name="check_no" type="text" value="<?= esc(old('check_no')) ?>" :disabled="method !== 'check'">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto pb-2">
            <div class="flex min-w-[1480px] gap-6">
                <div class="card p-4 w-[460px] shrink-0">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold">Unpaid Delivery Receipts</h2>
                        <span class="text-xs muted" x-text="visibleDeliveries.length + ' items'"></span>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>DR#</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Balance</th>
                                    <th class="text-right">Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="visibleDeliveries.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-3">No unpaid deliveries.</td>
                                    </tr>
                                </template>
                                <template x-for="delivery in visibleDeliveries" :key="delivery.delivery_id">
                                    <tr>
                                        <td x-text="delivery.dr_no"></td>
                                        <td x-text="delivery.date"></td>
                                        <td x-text="delivery.due_date || '-'"></td>
                                        <td x-text="formatAmount(delivery.working_balance)"></td>
                                        <td class="text-right">
                                            <button class="btn btn-secondary btn-strong" type="button" @click="openPayModal(delivery)" :disabled="Number(delivery.working_balance) <= 0">Pay</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 flex items-center justify-between total-highlight">
                        <span>Total Balance</span>
                        <span x-text="formatAmount(visibleDeliveriesTotal())"></span>
                    </div>

                    <?php if (! empty($unpaidPagerLinks)): ?>
                        <div class="mt-4 flex justify-end">
                            <?= $unpaidPagerLinks ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-4 w-[420px] shrink-0">
                    <h2 class="text-sm font-semibold">Allocations</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>DR#</th>
                                    <th>Amount</th>
                                    <th class="text-right">x</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="allocations.length === 0">
                                    <tr>
                                        <td colspan="4" class="py-3">No allocations yet.</td>
                                    </tr>
                                </template>
                                <template x-for="(allocation, index) in allocations" :key="allocation.delivery_id + '-' + index">
                                    <tr>
                                        <td x-text="allocation.dr_no"></td>
                                        <td x-text="formatAmount(allocation.amount)"></td>
                                        <td class="text-left">
                                            <button class="btn-link" type="button" @click="removeAllocation(index)">x</button>
                                            <input type="hidden" :name="'allocations[' + index + '][delivery_id]'" :value="allocation.delivery_id">
                                            <input type="hidden" :name="'allocations[' + index + '][amount]'" :value="allocation.amount">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card p-4 w-[300px] shrink-0" x-show="!isApplyingAdvance()" x-cloak>
                    <h2 class="text-sm font-semibold">Other Accounts </h2>
                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="block text-sm font-medium" for="sales_discount">Sales Discount</label>
                            <input class="input mt-1" id="sales_discount" name="sales_discount" type="number" step="0.01" min="0" x-model="salesDiscount">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="delivery_charges">Delivery Charges</label>
                            <input class="input mt-1" id="delivery_charges" name="delivery_charges" type="number" step="0.01" min="0" x-model="deliveryCharges">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="taxes">Taxes</label>
                            <input class="input mt-1" id="taxes" name="taxes" type="number" step="0.01" min="0" x-model="taxes">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="commissions">Commissions</label>
                            <input class="input mt-1" id="commissions" name="commissions" type="number" step="0.01" min="0" x-model="commissions">
                        </div>
                    </div>
                </div>

                <div class="card p-4 w-[300px] shrink-0" x-show="!isApplyingAdvance()" x-cloak>
                    <h2 class="text-sm font-semibold">A/R Other</h2>
                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="block text-sm font-medium" for="ar_other_description">Description</label>
                            <input class="input mt-1" id="ar_other_description" name="ar_other_description" type="text" x-model="arOtherDescription">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="ar_other_amount">Amount</label>
                            <input class="input mt-1" id="ar_other_amount" name="ar_other_amount" type="number" step="0.01" min="0" x-model="arOtherAmount">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 text-sm">
                <div>
                    <p class="muted" x-text="isApplyingAdvance() ? 'Advance Balance' : 'Amount Received'"></p>
                    <p class="font-semibold" x-text="formatAmount(amountReceived)"></p>
                </div>
                <div>
                    <p class="muted">Allocated Total</p>
                    <p class="font-semibold" x-text="formatAmount(allocatedTotal())"></p>
                </div>
                <div x-show="!isApplyingAdvance()" x-cloak>
                    <p class="muted">Other Accounts Total</p>
                    <p class="font-semibold" x-text="formatAmount(fixedAccountsTotal())"></p>
                </div>
                <div x-show="!isApplyingAdvance()" x-cloak>
                    <p class="muted">A/R Other Total</p>
                    <p class="font-semibold" x-text="formatAmount(arOtherAmount)"></p>
                </div>
                <div>
                    <p class="muted" x-text="isApplyingAdvance() ? 'Advance Remaining' : 'Unallocated'"></p>
                    <p class="font-semibold" x-text="formatAmount(isApplyingAdvance() ? advanceRemainingAfterAllocation() : balanceAmount())"></p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-3">
                <button class="btn btn-strong" type="submit" :disabled="!canSubmit()" x-text="isApplyingAdvance() ? 'Apply Advance PR' : 'Commit Transaction'"></button>
                <a class="btn btn-secondary" href="<?= base_url('payments') ?>">Cancel</a>
            </div>
        </div>
    </form>

    <div class="modal-backdrop" x-show="advanceModalOpen" x-cloak @click.self="advanceModalOpen = false">
        <div class="modal-panel max-w-3xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Advance Collections</h2>
                    <p class="mt-1 text-sm muted">Previous PRs with remaining balances for this client.</p>
                </div>
                <button class="btn btn-secondary" type="button" @click="advanceModalOpen = false">Close</button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>PR#</th>
                            <th>Received</th>
                            <th>Allocated</th>
                            <th>Balance</th>
                            <th class="text-right">Choose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="advanceCollections.length === 0">
                            <tr>
                                <td colspan="6" class="py-3">No advance collections available.</td>
                            </tr>
                        </template>
                        <template x-for="advance in advanceCollections" :key="advance.payment_id">
                            <tr>
                                <td x-text="advance.date"></td>
                                <td x-text="advance.pr_no"></td>
                                <td x-text="formatAmount(advance.amount_received)"></td>
                                <td x-text="formatAmount(advance.amount_allocated)"></td>
                                <td x-text="formatAmount(advance.balance)"></td>
                                <td class="text-right">
                                    <button class="btn btn-secondary btn-strong" type="button" @click="chooseAdvance(advance)">Choose</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="modalOpen" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold">Allocate Payment</h2>
            <p class="mt-1 text-sm muted" x-text="modalDelivery ? 'DR# ' + modalDelivery.dr_no : ''"></p>

            <div class="mt-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span>Date</span>
                    <span x-text="modalDelivery ? modalDelivery.date : ''"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Balance</span>
                    <span x-text="modalDelivery ? formatAmount(modalDelivery.working_balance) : ''"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="pay_amount">Amount to Pay</label>
                    <input class="input mt-1" id="pay_amount" type="number" step="0.01" min="0" x-model="modalAmount">
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button class="btn btn-strong" type="button" @click="confirmAllocation()">OK</button>
                <button class="btn btn-secondary" type="button" @click="closePayModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    function paymentForm() {
        return {
            deliveries: [],
            advanceCollections: [],
            allocations: [],
            selectedAdvancePaymentId: '<?= esc(old('advance_payment_id') ?: '') ?>',
            advanceModalOpen: false,
            hasActiveReceipt: <?= $activeReceipt ? 'true' : 'false' ?>,
            rangeEnd: '<?= esc((string) ($rangeEnd ?? '')) ?>',
            method: '<?= esc(old('method') ?: 'cash') ?>',
            amountReceived: '<?= esc(old('amount_received')) ?>',
            arOtherDescription: '<?= esc(old('ar_other_description')) ?>',
            arOtherAmount: '<?= esc(old('ar_other_amount')) ?>',
            salesDiscount: '<?= esc(old('sales_discount')) ?>',
            deliveryCharges: '<?= esc(old('delivery_charges')) ?>',
            taxes: '<?= esc(old('taxes')) ?>',
            commissions: '<?= esc(old('commissions')) ?>',
            modalOpen: false,
            modalDelivery: null,
            modalAmount: '',
            normalizeAmount(value) {
                return Math.round((parseFloat(value) || 0) * 100) / 100;
            },
            init() {
                const deliveries = this.parseJson(this.$el.dataset.deliveries, []);
                this.advanceCollections = this.parseJson(this.$el.dataset.advances, []);
                this.deliveries = deliveries.map((delivery) => ({
                    ...delivery,
                    working_balance: this.normalizeAmount(delivery.balance)
                }));
                if (this.selectedAdvance()) {
                    this.amountReceived = this.formatInputAmount(this.selectedAdvance().balance);
                }
            },
            get visibleDeliveries() {
                return this.deliveries.filter((delivery) => Number(delivery.working_balance) > 0);
            },
            visibleDeliveriesTotal() {
                return this.visibleDeliveries.reduce((sum, delivery) => {
                    return sum + (parseFloat(delivery.working_balance) || 0);
                }, 0);
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
            parseJson(value, fallback) {
                if (!value) {
                    return fallback;
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    return fallback;
                }
            },
            allocatedTotal() {
                return this.allocations.reduce((sum, allocation) => sum + (parseFloat(allocation.amount) || 0), 0);
            },
            fixedAccountsTotal() {
                return [this.salesDiscount, this.deliveryCharges, this.taxes, this.commissions]
                    .reduce((sum, value) => sum + (parseFloat(value) || 0), 0);
            },
            balanceAmount() {
                return (parseFloat(this.amountReceived) || 0)
                    + this.fixedAccountsTotal()
                    - this.allocatedTotal()
                    - (parseFloat(this.arOtherAmount) || 0);
            },
            selectedAdvance() {
                return this.advanceCollections.find((row) => String(row.payment_id) === String(this.selectedAdvancePaymentId)) || null;
            },
            isApplyingAdvance() {
                return Boolean(this.selectedAdvance());
            },
            chooseAdvance(advance) {
                this.selectedAdvancePaymentId = advance.payment_id;
                this.amountReceived = this.formatInputAmount(advance.balance);
                this.salesDiscount = '';
                this.deliveryCharges = '';
                this.taxes = '';
                this.commissions = '';
                this.arOtherDescription = '';
                this.arOtherAmount = '';
                this.advanceModalOpen = false;
            },
            clearAdvance() {
                this.selectedAdvancePaymentId = '';
                this.amountReceived = '';
            },
            advanceRemainingAfterAllocation() {
                const advance = this.selectedAdvance();
                return (parseFloat(advance ? advance.balance : this.amountReceived) || 0) - this.allocatedTotal();
            },
            canSubmit() {
                if (this.isApplyingAdvance()) {
                    return this.allocations.length > 0 && this.advanceRemainingAfterAllocation() >= -0.005;
                }

                return this.hasActiveReceipt && (parseFloat(this.amountReceived) || 0) > 0 && this.balanceAmount() >= -0.005;
            },
            submitForm(event) {
                if (this.isApplyingAdvance()) {
                    if (this.allocations.length === 0) {
                        window.showToast('Add at least one DR allocation.', 'error');
                        return;
                    }
                    if (this.advanceRemainingAfterAllocation() < -0.005) {
                        window.showToast('Allocation exceeds the selected advance balance.', 'error');
                        return;
                    }
                } else if (this.balanceAmount() < -0.005) {
                    window.showToast('Allocated amount exceeds the amount available on this receipt.', 'error');
                    return;
                }

                event.target.submit();
            },
            openPayModal(delivery) {
                this.modalDelivery = delivery;
                this.modalAmount = '';
                this.modalOpen = true;
            },
            closePayModal() {
                this.modalOpen = false;
                this.modalDelivery = null;
                this.modalAmount = '';
            },
            confirmAllocation() {
                const amount = parseFloat(this.modalAmount) || 0;
                if (!this.modalDelivery || amount <= 0) {
                    return;
                }
                if (amount > this.modalDelivery.working_balance) {
                    alert('Amount exceeds delivery balance.');
                    return;
                }
                if (this.isApplyingAdvance() && amount > this.advanceRemainingAfterAllocation() + 0.005) {
                    alert('Amount exceeds selected advance balance.');
                    return;
                }

                this.modalDelivery.working_balance = this.normalizeAmount(this.modalDelivery.working_balance - amount);
                this.allocations.push({
                    delivery_id: this.modalDelivery.delivery_id,
                    dr_no: this.modalDelivery.dr_no,
                    amount: amount,
                    balance_after: this.modalDelivery.working_balance
                });

                this.closePayModal();
            },
            removeAllocation(index) {
                const allocation = this.allocations[index];
                if (!allocation) {
                    return;
                }
                const delivery = this.deliveries.find((row) => String(row.delivery_id) === String(allocation.delivery_id));
                if (delivery) {
                    delivery.working_balance = this.normalizeAmount(delivery.working_balance + parseFloat(allocation.amount));
                }
                this.allocations.splice(index, 1);
            }
        };
    }
</script>
<?= $this->endSection() ?>
