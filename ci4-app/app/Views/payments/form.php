<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$cashiersJson = json_encode($cashiers ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$deliveriesJson = json_encode($unpaidDeliveries ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
?>

<div
    x-data="paymentForm()"
    data-cashiers="<?= esc($cashiersJson, 'attr') ?>"
    data-deliveries="<?= esc($deliveriesJson, 'attr') ?>"
    data-current-excess="<?= esc((string) $currentExcess, 'attr') ?>">
    <h1 class="text-xl font-semibold">Payment for <?= esc($client['name']) ?></h1>
    <p class="mt-1 text-sm muted">Allocate receipts and commit when ready.</p>

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

        <div class="grid gap-6 lg:grid-cols-2">
            <div>
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
            </div>

            <div>
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
                        <span>Balance</span>
                        <span x-text="balanceAmount().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Excess</span>
                        <span x-text="currentExcess.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="btn" type="submit">Commit Transaction</button>
            <a class="btn btn-secondary" href="<?= base_url('payments') ?>">Cancel</a>
        </div>
    </form>

    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40" x-show="modalOpen" x-cloak>
        <div class="card w-full max-w-md p-6">
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
            currentExcess: 0,
            allocations: [],
            selectedCashierId: '<?= esc(old('cashier_id')) ?>',
            activeReceipt: '',
            method: '<?= esc(old('method') ?: 'cash') ?>',
            amountReceived: '<?= esc(old('amount_received')) ?>',
            depositBankId: '<?= esc(old('deposit_bank_id')) ?>',
            modalOpen: false,
            modalDelivery: null,
            modalAmount: '',
            init() {
                const root = this.$el;
                this.cashiers = this.parseJson(root.dataset.cashiers, []);

                const deliveries = this.parseJson(root.dataset.deliveries, []);
                this.deliveries = deliveries.map((delivery) => ({
                    ...delivery,
                    working_balance: parseFloat(delivery.balance) || 0
                }));

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
            balanceAmount() {
                return (parseFloat(this.amountReceived) || 0) - this.allocatedTotal();
            },
            availableToAllocate() {
                return (parseFloat(this.amountReceived) || 0) + Math.max(0, this.currentExcess) - this.allocatedTotal();
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