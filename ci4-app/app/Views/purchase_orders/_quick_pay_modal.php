<?php

/**
 * @var array<string, mixed> $quickPayData
 */
$quickPayData = $quickPayData ?? [];
$assignedUser = $quickPayData['assignedUser'] ?? null;
$collectorLabel = trim((string) (($assignedUser['name'] ?? '') . ' (' . ($assignedUser['username'] ?? '-') . ')'));
$activeReceipt = $quickPayData['activeReceipt'] ?? null;
$rangeEnd = $quickPayData['rangeEnd'] ?? null;
$banks = $quickPayData['banks'] ?? [];
?>

<div class="modal-backdrop" x-show="quickPayOpen" x-cloak @click.self="closeQuickPay()">
    <div class="modal-panel max-h-[92vh] max-w-4xl overflow-y-auto p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Pay Supplier</h2>
                <p class="mt-1 text-sm muted" x-text="selectedQuickPayOrder() ? 'RR# ' + selectedQuickPayOrder().po_no : ''"></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeQuickPay()">Close</button>
        </div>

        <?php if (! $activeReceipt): ?>
            <div class="card mb-4 p-4">
                <p class="text-sm text-red-700">No active receipt range is assigned to your user yet. Ask admin to assign a range before posting payables.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('payables/quick-pay') ?>" class="space-y-5" x-on:submit.prevent="if (Math.abs(quickPayBalanceAmount()) > 0.005) { window.showToast('Unallocated amount must be zero before committing.', 'error'); return; } $el.submit();">
            <?= csrf_field() ?>
            <input type="hidden" name="supplier_id" :value="selectedQuickPayOrder() ? selectedQuickPayOrder().supplier_id : ''">
            <input type="hidden" name="purchase_order_id" :value="selectedQuickPayOrder() ? selectedQuickPayOrder().id : ''">

            <div class="card p-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium">Cashier</label>
                        <input class="input mt-1" value="<?= esc($collectorLabel) ?>" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Active CV</label>
                        <input class="input mt-1" value="<?= $activeReceipt ? esc((string) $activeReceipt) : 'Not assigned' ?>" readonly>
                        <?php if ($activeReceipt && $rangeEnd): ?>
                            <p class="mt-1 text-xs muted">Range end: <?= esc((string) $rangeEnd) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="quick_date">Date</label>
                        <input class="input mt-1" id="quick_date" name="date" type="date" x-model="quickPay.date" required>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium" for="quick_method">Payment Method</label>
                        <select class="input mt-1" id="quick_method" name="method" x-model="quickPay.method" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="quick_amount_received">Amount Paid</label>
                        <input class="input mt-1" id="quick_amount_received" name="amount_received" type="number" step="0.01" min="0" x-model="quickPay.amountReceived" @input="syncAllocationFromReceived()" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="quick_deposit_bank_id">Bank</label>
                        <select class="input mt-1" id="quick_deposit_bank_id" name="deposit_bank_id" x-model="quickPay.depositBankId" required>
                            <option value="">Select bank</option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?= esc((string) $bank['id']) ?>"><?= esc((string) $bank['bank_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div x-show="quickPay.method === 'bank' || quickPay.method === 'check'" x-cloak>
                        <label class="block text-sm font-medium" for="quick_payer_bank">Supplier Bank</label>
                        <input class="input mt-1" id="quick_payer_bank" name="payer_bank" x-model="quickPay.payerBank" :disabled="quickPay.method === 'cash'">
                    </div>
                    <div x-show="quickPay.method === 'check'" x-cloak>
                        <label class="block text-sm font-medium" for="quick_check_no">Check Number</label>
                        <input class="input mt-1" id="quick_check_no" name="check_no" x-model="quickPay.checkNo" :disabled="quickPay.method !== 'check'">
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div class="card p-4">
                    <h3 class="text-sm font-semibold">Selected RR</h3>
                    <div class="mt-4 space-y-3 text-sm" x-show="selectedQuickPayOrder()">
                        <div class="flex justify-between">
                            <span class="muted">RR#</span>
                            <span x-text="selectedQuickPayOrder() ? selectedQuickPayOrder().po_no : ''"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="muted">Balance</span>
                            <span class="font-semibold" x-text="formatAmount(selectedQuickPayOrder() ? selectedQuickPayOrder().balance : 0)"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="quick_allocation_amount">Amount to Allocate</label>
                            <input class="input mt-1" id="quick_allocation_amount" name="allocation_amount" type="number" step="0.01" min="0" x-model="quickPay.allocationAmount" required>
                        </div>
                    </div>
                </div>
                <div class="card p-4">
                    <h3 class="text-sm font-semibold">Other Accounts</h3>
                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="block text-sm font-medium">Discount</label>
                            <input class="input mt-1" name="sales_discount" type="number" step="0.01" min="0" x-model="quickPay.salesDiscount">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Charges</label>
                            <input class="input mt-1" name="delivery_charges" type="number" step="0.01" min="0" x-model="quickPay.deliveryCharges">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Taxes</label>
                            <input class="input mt-1" name="taxes" type="number" step="0.01" min="0" x-model="quickPay.taxes">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Commissions</label>
                            <input class="input mt-1" name="commissions" type="number" step="0.01" min="0" x-model="quickPay.commissions">
                        </div>
                    </div>
                </div>
                <?php /* A/P Other is hidden until the payable-side accounting treatment is confirmed. */ ?>
            </div>

            <div class="card p-4">
                <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="muted">Amount Paid</p>
                        <p class="font-semibold" x-text="formatAmount(quickPay.amountReceived)"></p>
                    </div>
                    <div>
                        <p class="muted">Allocated</p>
                        <p class="font-semibold" x-text="formatAmount(quickPay.allocationAmount)"></p>
                    </div>
                    <div>
                        <p class="muted">Other Accounts</p>
                        <p class="font-semibold" x-text="formatAmount(quickPayFixedAccountsTotal())"></p>
                    </div>
                    <div>
                        <p class="muted">Unallocated</p>
                        <p class="font-semibold" x-text="formatAmount(quickPayBalanceAmount())"></p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3">
                    <button class="btn btn-strong" type="submit" :disabled="<?= $activeReceipt ? 'false' : 'true' ?> || !selectedQuickPayOrder()">Commit Payable</button>
                    <button class="btn btn-secondary" type="button" @click="closeQuickPay()">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>