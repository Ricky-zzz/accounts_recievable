<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$cashiersJson = json_encode($cashiers ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$deliveriesJson = json_encode($unpaidDeliveries ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$otherAccountsJson = json_encode($otherAccounts ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
?>

<div
    x-data="paymentForm()"
    data-cashiers="<?= esc($cashiersJson, 'attr') ?>"
    data-deliveries="<?= esc($deliveriesJson, 'attr') ?>"
    data-other-accounts="<?= esc($otherAccountsJson, 'attr') ?>"
    data-current-excess="<?= esc((string) $currentExcess, 'attr') ?>">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Payment for <?= esc($client['name']) ?></h1>
            <p class="mt-1 text-sm muted">Allocate receipts and commit when ready.</p>
        </div>

        <a class="btn btn-secondary" href="<?= base_url('payments/client/' . $client['id']) ?>">Back</a>
    </div>

    <form class="mt-6 space-y-6" method="post" action="<?= base_url('payments') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="client_id" value="<?= esc($client['id']) ?>">

        <div class="card p-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium" for="cashier_id">Cashier</label>
                    <select class="input mt-1" id="cashier_id" name="cashier_id" x-model="selectedCashierId" required>
                        <option value="">Select cashier</option>
                        <?php foreach ($cashiers as $cashier): ?>
                            <option value="<?= esc($cashier['id']) ?>" <?= (string) old('cashier_id') === (string) $cashier['id'] ? 'selected' : '' ?>>
                                <?= esc($cashier['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs muted" x-show="selectedCashierId && !activeReceipt">No active receipt range.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="pr_no">Active Receipt</label>
                    <input class="input mt-1" id="pr_no" type="text" :value="activeReceipt" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="date">Date</label>
                    <input class="input mt-1" id="date" name="date" type="date" value="<?= esc(old('date') ?: date('Y-m-d')) ?>" required>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium" for="method">Payment Method</label>
                    <select class="input mt-1" id="method" name="method" x-model="method" required>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="check">Check</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="amount_received">Amount Received</label>
                    <input class="input mt-1" id="amount_received" name="amount_received" type="number" step="0.01" min="0" x-model="amountReceived" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="deposit_bank_id">Deposit Bank</label>
                    <select class="input mt-1" id="deposit_bank_id" name="deposit_bank_id" x-model="depositBankId" required>
                        <option value="">Select bank</option>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?= esc($bank['id']) ?>"><?= esc($bank['bank_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="method === 'bank'">
                <div>
                    <label class="block text-sm font-medium" for="payer_bank">Bank of Payer</label>
                    <input class="input mt-1" id="payer_bank" name="payer_bank" type="text" value="<?= esc(old('payer_bank')) ?>" :disabled="method !== 'bank'">
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="method === 'check'">
                <div>
                    <label class="block text-sm font-medium" for="check_no">Check Number</label>
                    <input class="input mt-1" id="check_no" name="check_no" type="text" value="<?= esc(old('check_no')) ?>" :disabled="method !== 'check'">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="check_bank">Bank of Payer</label>
                    <input class="input mt-1" id="check_bank" name="payer_bank" type="text" value="<?= esc(old('payer_bank')) ?>" :disabled="method !== 'check'">
                </div>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto pb-4">
            <div class="flex gap-10 min-w-[1700px]">
                <div class="min-w-[320px]">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold">Unpaid Delivery Receipts</h2>
                        <span class="text-xs muted" x-text="deliveries.length + ' items'"></span>
                    </div>
                    <table class="table mt-4">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>DR#</th>
                                <th>Balance</th>
                                <th class="text-right">Pay</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="deliveries.length === 0">
                                <tr>
                                    <td class="py-3" colspan="4">No unpaid deliveries.</td>
                                </tr>
                            </template>
                            <template x-for="delivery in deliveries" :key="delivery.id">
                                <tr x-show="delivery.working_balance > 0">
                                    <td x-text="delivery.date"></td>
                                    <td x-text="delivery.dr_no"></td>
                                    <td x-text="Number(delivery.working_balance).toFixed(2)"></td>
                                    <td class="text-left">
                                        <button class="btn btn-secondary" type="button" @click="openPayModal(delivery)">Pay</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Total </span>
                            <span x-text="unpaidTotal().toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <div class="min-w-[320px]">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold">Allocations</h2>
                        <span class="text-xs muted" x-text="allocations.length + ' allocations'"></span>
                    </div>
                    <table class="table mt-4">
                        <thead>
                            <tr>
                                <th>DR#</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th class="text-right">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="allocations.length === 0">
                                <tr>
                                    <td class="py-3" colspan="4">No allocations yet.</td>
                                </tr>
                            </template>
                            <template x-for="(allocation, index) in allocations" :key="allocation.delivery_id + '-' + index">
                                <tr>
                                    <td x-text="allocation.dr_no"></td>
                                    <td x-text="Number(allocation.amount).toFixed(2)"></td>
                                    <td x-text="Number(allocation.balance_after).toFixed(2)"></td>
                                    <td class="text-right">
                                        <button class="btn-link" type="button" @click="removeAllocation(index)">Remove</button>
                                        <input type="hidden" :name="'allocations[' + index + '][delivery_id]'" :value="allocation.delivery_id">
                                        <input type="hidden" :name="'allocations[' + index + '][amount]'" :value="allocation.amount">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Allocated</span>
                            <span x-text="allocatedTotal().toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Other Accounts Total</span>
                            <span x-text="diffDrCr().toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>A/R Trade Total</span>
                            <span x-text="arTradeTotal().toFixed(2)"></span>
                        </div>

                    </div>
                </div>

                <div class="min-w-[440px]">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold">Other Accounts</h2>
                        <button class="btn btn-secondary" type="button" @click="openOtherAccountModal()">+ Other Account</button>
                    </div>
                    <p class="mt-1 text-xs muted" x-text="otherAccountRows.length + ' items'"></p>

                    <table class="table mt-4">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Impact</th>
                                <th>Amount</th>
                                <th class="text-right">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="otherAccountRows.length === 0">
                                <tr>
                                    <td class="py-3" colspan="6">No other accounts yet.</td>
                                </tr>
                            </template>
                            <template x-for="(row, index) in otherAccountRows" :key="row.account_id + '-' + index">
                                <tr>
                                    <td x-text="row.account_name"></td>
                                    <td x-text="row.reference || '-'" class="text-xs"></td>
                                    <td x-text="row.type.toUpperCase()"></td>
                                    <td x-text="row.affects_trade ? (row.type === 'dr' ? 'Add' : 'Subtract') : 'Independent'"></td>
                                    <td x-text="Number(row.amount).toFixed(2)"></td>
                                    <td class="text-right">
                                        <button class="btn-link" type="button" @click="removeOtherAccount(index)">Remove</button>
                                        <input type="hidden" :name="'other_accounts[' + index + '][account_id]'" :value="row.account_id">
                                        <input type="hidden" :name="'other_accounts[' + index + '][reference]'" :value="row.reference">
                                        <input type="hidden" :name="'other_accounts[' + index + '][note]'" :value="row.note">
                                        <input type="hidden" :name="'other_accounts[' + index + '][amount]'" :value="row.amount">
                                        <input type="hidden" :name="'other_accounts[' + index + '][affects_trade]'" :value="row.affects_trade ? 1 : 0">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>DR Total</span>
                            <span x-text="otherDrTotal().toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>CR Total</span>
                            <span x-text="otherCrTotal().toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <div class="min-w-[380px]">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold">A/R Other</h2>
                        <button class="btn btn-secondary" type="button" @click="openArOtherModal()">+ A/R Other</button>
                    </div>
                    <p class="mt-1 text-xs muted" x-text="arOtherRows.length + ' items'"></p>

                    <table class="table mt-4">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th class="text-right">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="arOtherRows.length === 0">
                                <tr>
                                    <td class="py-3" colspan="3">No A/R other yet.</td>
                                </tr>
                            </template>
                            <template x-for="(row, index) in arOtherRows" :key="row.description + '-' + index">
                                <tr>
                                    <td x-text="row.description || '-'" class="text-xs"></td>
                                    <td x-text="Number(row.amount).toFixed(2)"></td>
                                    <td class="text-right">
                                        <button class="btn-link" type="button" @click="removeArOther(index)">Remove</button>
                                        <input type="hidden" :name="'ar_others[' + index + '][description]'" :value="row.description">
                                        <input type="hidden" :name="'ar_others[' + index + '][amount]'" :value="row.amount">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>A/R Others Total</span>
                            <span x-text="arOtherTotal().toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[240px]">
                <label class="block text-sm font-medium" for="unallocated_amount">Unallocated Amount</label>
                <input
                    class="input mt-1 text-right"
                    id="unallocated_amount"
                    type="text"
                    :value="balanceAmount().toFixed(2)"
                    readonly>
            </div>
            <button class="btn" type="submit">Commit Transaction</button>
            <a class="btn btn-secondary" href="<?= base_url('payments') ?>">Cancel</a>
        </div>
    </form>

    <div class="modal-backdrop" x-show="otherAccountModalOpen" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold">Add Other Account</h2>
            <div class="mt-4 grid gap-3">
                <div>
                    <label class="block text-sm font-medium" for="modal_other_account_id">Account</label>
                    <select class="input mt-1" id="modal_other_account_id" x-model="otherAccountForm.account_id">
                        <option value="">Select account</option>
                        <template x-for="account in otherAccounts" :key="account.id">
                            <option :value="account.id" x-text="`${account.name} - ${account.type}`"></option>
                        </template>
                    </select>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="modal_other_reference">Reference</label>
                        <input class="input mt-1" id="modal_other_reference" type="text" x-model="otherAccountForm.reference" placeholder="DR101">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="modal_other_amount">Amount</label>
                        <input class="input mt-1" id="modal_other_amount" type="number" step="0.01" min="0" x-model="otherAccountForm.amount">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="modal_other_note">Note</label>
                        <input class="input mt-1" id="modal_other_note" type="text" x-model="otherAccountForm.note" placeholder="Optional note">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="modal_other_affects_trade">AR Trade Impact</label>
                        <select class="input mt-1" id="modal_other_affects_trade" x-model="otherAccountForm.affects_trade">
                            <option value="1">Affects AR Trade</option>
                            <option value="0">Independent</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button class="btn" type="button" @click="addOtherAccount()">Add Entry</button>
                <button class="btn btn-secondary" type="button" @click="closeOtherAccountModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="arOtherModalOpen" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold">Add A/R Other</h2>
            <div class="mt-4 grid gap-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="modal_ar_other_description">Description</label>
                        <input class="input mt-1" id="modal_ar_other_description" type="text" x-model="arOtherForm.description" placeholder="Description">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="modal_ar_other_amount">Amount</label>
                        <input class="input mt-1" id="modal_ar_other_amount" type="number" step="0.01" min="0" x-model="arOtherForm.amount">
                    </div>
                </div>
                <div class="text-xs muted">A/R Other entries do not affect AR Trade.</div>
            </div>
            <div class="mt-4 flex gap-3">
                <button class="btn" type="button" @click="addArOther()">Add Entry</button>
                <button class="btn btn-secondary" type="button" @click="closeArOtherModal()">Cancel</button>
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
                    <span x-text="modalDelivery ? Number(modalDelivery.working_balance).toFixed(2) : ''"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="pay_amount">Amount to Pay</label>
                    <input class="input mt-1" id="pay_amount" type="number" step="0.01" min="0" x-model="modalAmount">
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button class="btn" type="button" @click="confirmAllocation()">OK</button>
                <button class="btn btn-secondary" type="button" @click="closePayModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    function paymentForm() {
        return {
            cashiers: [],
            deliveries: [],
            otherAccounts: [],
            currentExcess: 0,
            allocations: [],
            otherAccountRows: [],
            arOtherRows: [],
            selectedCashierId: '<?= esc(old('cashier_id')) ?>',
            activeReceipt: '',
            method: '<?= esc(old('method') ?: 'cash') ?>',
            amountReceived: '<?= esc(old('amount_received')) ?>',
            depositBankId: '<?= esc(old('deposit_bank_id')) ?>',
            modalOpen: false,
            modalDelivery: null,
            modalAmount: '',
            otherAccountModalOpen: false,
            arOtherModalOpen: false,
            otherAccountForm: {
                account_id: '',
                reference: '',
                note: '',
                amount: '',
                affects_trade: '1',
            },
            arOtherForm: {
                description: '',
                amount: '',
            },
            init() {
                const root = this.$el;
                this.cashiers = this.parseJson(root.dataset.cashiers, []);

                const deliveries = this.parseJson(root.dataset.deliveries, []);
                this.deliveries = deliveries.map((delivery) => ({
                    ...delivery,
                    working_balance: parseFloat(delivery.balance) || 0
                }));

                this.otherAccounts = this.parseJson(root.dataset.otherAccounts, []);

                this.currentExcess = parseFloat(root.dataset.currentExcess) || 0;
                this.updateReceipt();
                this.$watch('selectedCashierId', () => {
                    this.updateReceipt();
                });
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
            updateReceipt() {
                const cashier = this.cashiers.find((row) => String(row.id) === String(this.selectedCashierId));
                this.activeReceipt = cashier && cashier.active_receipt ? cashier.active_receipt : '';
            },
            allocatedTotal() {
                return this.allocations.reduce((sum, allocation) => sum + (parseFloat(allocation.amount) || 0), 0);
            },
            unpaidTotal() {
                return this.deliveries
                    .filter((row) => parseFloat(row.working_balance) > 0)
                    .reduce((sum, row) => sum + (parseFloat(row.working_balance) || 0), 0);
            },
            otherDrTotal() {
                return this.otherAccountRows
                    .filter((row) => row.type === 'dr')
                    .reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
            },
            otherCrTotal() {
                return this.otherAccountRows
                    .filter((row) => row.type === 'cr')
                    .reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
            },
            diffDrCr() {
                return this.otherDrTotal() - this.otherCrTotal();
            },
            otherDrAffectTotal() {
                return this.otherAccountRows
                    .filter((row) => row.type === 'dr' && row.affects_trade)
                    .reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
            },
            otherCrAffectTotal() {
                return this.otherAccountRows
                    .filter((row) => row.type === 'cr' && row.affects_trade)
                    .reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
            },
            arOtherTotal() {
                return this.arOtherRows.reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
            },
            arTradeTotal() {
                return this.allocatedTotal() + this.otherDrAffectTotal() - this.otherCrAffectTotal();
            },
            balanceAmount() {
                return (parseFloat(this.amountReceived) || 0) - this.allocatedTotal() - this.arOtherTotal();
            },
            availableToAllocate() {
                return (parseFloat(this.amountReceived) || 0) +
                    Math.max(0, this.currentExcess) +
                    this.otherDrAffectTotal() -
                    this.allocatedTotal();
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
            openOtherAccountModal() {
                this.otherAccountModalOpen = true;
            },
            closeOtherAccountModal() {
                this.otherAccountModalOpen = false;
                this.clearOtherAccountForm();
            },
            openArOtherModal() {
                this.arOtherModalOpen = true;
            },
            closeArOtherModal() {
                this.arOtherModalOpen = false;
                this.clearArOtherForm();
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
                if (amount > this.availableToAllocate()) {
                    alert('Amount exceeds available allocation.');
                    return;
                }

                this.modalDelivery.working_balance = parseFloat((this.modalDelivery.working_balance - amount).toFixed(2));
                this.allocations.push({
                    delivery_id: this.modalDelivery.id,
                    dr_no: this.modalDelivery.dr_no,
                    amount: amount,
                    balance_after: this.modalDelivery.working_balance
                });

                this.closePayModal();
            },
            addOtherAccount() {
                const accountId = parseInt(this.otherAccountForm.account_id, 10);
                const amount = parseFloat(this.otherAccountForm.amount) || 0;
                const affectsTrade = this.otherAccountForm.affects_trade === '1';
                const reference = (this.otherAccountForm.reference || '').trim();

                if (!accountId || amount <= 0) {
                    alert('Select an account and enter a valid amount.');
                    return;
                }

                const account = this.otherAccounts.find((row) => String(row.id) === String(accountId));
                if (!account) {
                    alert('Selected account not found.');
                    return;
                }

                // If reference matches a delivery DR# and account is DR type, deduct from that delivery
                if (reference && account.type === 'dr') {
                    const allocation = this.allocations.find((row) => row.dr_no === reference);
                    if (allocation) {
                        // Find the original delivery to verify sufficient balance
                        const delivery = this.deliveries.find((row) => String(row.id) === String(allocation.delivery_id));
                        if (delivery && delivery.working_balance >= 0) {
                            if (amount > delivery.working_balance) {
                                alert(`Discount amount (${amount}) exceeds remaining balance (${delivery.working_balance.toFixed(2)}) for ${reference}.`);
                                return;
                            }
                            // Deduct discount from delivery's working balance
                            delivery.working_balance = parseFloat((delivery.working_balance - amount).toFixed(2));
                            allocation.balance_after = delivery.working_balance;
                        }
                    }
                }

                this.otherAccountRows.push({
                    account_id: account.id,
                    account_name: account.name,
                    type: account.type,
                    reference: reference,
                    note: (this.otherAccountForm.note || '').trim(),
                    amount: amount,
                    affects_trade: affectsTrade,
                });

                this.closeOtherAccountModal();
            },
            clearOtherAccountForm() {
                this.otherAccountForm = {
                    account_id: '',
                    reference: '',
                    note: '',
                    amount: '',
                    affects_trade: '1',
                };
            },
            removeOtherAccount(index) {
                if (index < 0 || index >= this.otherAccountRows.length) {
                    return;
                }
                const row = this.otherAccountRows[index];

                // If this was a discount linked to a delivery, restore its balance
                if (row.reference && row.type === 'dr') {
                    const allocation = this.allocations.find((a) => a.dr_no === row.reference);
                    if (allocation) {
                        const delivery = this.deliveries.find((d) => String(d.id) === String(allocation.delivery_id));
                        if (delivery) {
                            delivery.working_balance = parseFloat((delivery.working_balance + parseFloat(row.amount)).toFixed(2));
                            allocation.balance_after = delivery.working_balance;
                        }
                    }
                }

                this.otherAccountRows.splice(index, 1);
            },
            addArOther() {
                const description = (this.arOtherForm.description || '').trim();
                const amount = parseFloat(this.arOtherForm.amount) || 0;
                if (amount <= 0) {
                    alert('Enter a valid amount.');
                    return;
                }

                this.arOtherRows.push({
                    description: description,
                    amount: amount,
                });

                this.closeArOtherModal();
            },
            clearArOtherForm() {
                this.arOtherForm = {
                    description: '',
                    amount: '',
                };
            },
            removeArOther(index) {
                if (index < 0 || index >= this.arOtherRows.length) {
                    return;
                }
                this.arOtherRows.splice(index, 1);
            },
            removeAllocation(index) {
                const allocation = this.allocations[index];
                if (!allocation) {
                    return;
                }
                const delivery = this.deliveries.find((row) => String(row.id) === String(allocation.delivery_id));
                if (delivery) {
                    delivery.working_balance = parseFloat((delivery.working_balance + parseFloat(allocation.amount)).toFixed(2));
                }
                this.allocations.splice(index, 1);
            }
        };
    }
</script>
<?= $this->endSection() ?>