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
                <h2 class="text-lg font-semibold">Collect Payment</h2>
                <p class="mt-1 text-sm muted" x-text="selectedQuickPayDelivery() ? 'DR# ' + selectedQuickPayDelivery().dr_no : ''"></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeQuickPay()">Close</button>
        </div>

        <?php if (! $activeReceipt): ?>
            <div class="card mb-4 p-4">
                <p class="text-sm text-red-700">No active receipt range is assigned to your user yet. Ask admin to assign a range before posting payments.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('payments/quick-pay') ?>" class="space-y-5" x-on:submit.prevent="if (Math.abs(quickPayBalanceAmount()) > 0.005) { window.showToast('Unallocated amount must be zero before committing.', 'error'); return; } $el.submit();">
            <?= csrf_field() ?>
            <input type="hidden" name="client_id" :value="selectedQuickPayDelivery() ? selectedQuickPayDelivery().client_id : ''">
            <input type="hidden" name="delivery_id" :value="selectedQuickPayDelivery() ? selectedQuickPayDelivery().id : ''">

            <div class="card p-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium">Collector</label>
                        <input class="input mt-1" type="text" value="<?= esc($collectorLabel) ?>" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Active Receipt</label>
                        <input class="input mt-1" type="text" value="<?= $activeReceipt ? esc((string) $activeReceipt) : 'Not assigned' ?>" readonly>
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
                        <label class="block text-sm font-medium" for="quick_amount_received">Amount Received</label>
                        <input class="input mt-1" id="quick_amount_received" name="amount_received" type="number" step="0.01" min="0" x-model="quickPay.amountReceived" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="quick_deposit_bank_id">Deposit Bank</label>
                        <select class="input mt-1" id="quick_deposit_bank_id" name="deposit_bank_id" x-model="quickPay.depositBankId" required>
                            <option value="">Select bank</option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?= esc((string) $bank['id']) ?>"><?= esc((string) $bank['bank_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div x-show="quickPay.method === 'bank' || quickPay.method === 'check'" x-cloak>
                        <label class="block text-sm font-medium" for="quick_payer_bank">Bank of Payer</label>
                        <input class="input mt-1" id="quick_payer_bank" name="payer_bank" type="text" x-model="quickPay.payerBank" :disabled="quickPay.method === 'cash'">
                    </div>
                    <div x-show="quickPay.method === 'check'" x-cloak>
                        <label class="block text-sm font-medium" for="quick_check_no">Check Number</label>
                        <input class="input mt-1" id="quick_check_no" name="check_no" type="text" x-model="quickPay.checkNo" :disabled="quickPay.method !== 'check'">
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr_1fr]">
                <div class="card p-4">
                    <h3 class="text-sm font-semibold">Selected DR</h3>
                    <div class="mt-4 space-y-3 text-sm" x-show="selectedQuickPayDelivery()">
                        <div class="flex justify-between gap-4">
                            <span class="muted">DR#</span>
                            <span class="font-medium" x-text="selectedQuickPayDelivery() ? selectedQuickPayDelivery().dr_no : ''"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="muted">Date</span>
                            <span x-text="selectedQuickPayDelivery() ? selectedQuickPayDelivery().date : ''"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="muted">Due Date</span>
                            <span x-text="selectedQuickPayDelivery() ? (selectedQuickPayDelivery().due_date || '-') : ''"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="muted">Balance</span>
                            <span class="font-semibold" x-text="formatAmount(selectedQuickPayDelivery() ? selectedQuickPayDelivery().balance : 0)"></span>
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
                            <label class="block text-sm font-medium" for="quick_sales_discount">Sales Discount</label>
                            <input class="input mt-1" id="quick_sales_discount" name="sales_discount" type="number" step="0.01" min="0" x-model="quickPay.salesDiscount">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="quick_delivery_charges">Delivery Charges</label>
                            <input class="input mt-1" id="quick_delivery_charges" name="delivery_charges" type="number" step="0.01" min="0" x-model="quickPay.deliveryCharges">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="quick_taxes">Taxes</label>
                            <input class="input mt-1" id="quick_taxes" name="taxes" type="number" step="0.01" min="0" x-model="quickPay.taxes">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="quick_commissions">Commissions</label>
                            <input class="input mt-1" id="quick_commissions" name="commissions" type="number" step="0.01" min="0" x-model="quickPay.commissions">
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <h3 class="text-sm font-semibold">A/R Other</h3>
                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="block text-sm font-medium" for="quick_ar_other_description">Description</label>
                            <input class="input mt-1" id="quick_ar_other_description" name="ar_other_description" type="text" x-model="quickPay.arOtherDescription">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="quick_ar_other_amount">Amount</label>
                            <input class="input mt-1" id="quick_ar_other_amount" name="ar_other_amount" type="number" step="0.01" min="0" x-model="quickPay.arOtherAmount">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-4">
                <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <p class="muted">Amount Received</p>
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
                        <p class="muted">A/R Other</p>
                        <p class="font-semibold" x-text="formatAmount(quickPay.arOtherAmount)"></p>
                    </div>
                    <div>
                        <p class="muted">Unallocated</p>
                        <p class="font-semibold" x-text="formatAmount(quickPayBalanceAmount())"></p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3">
                    <button class="btn" type="submit" :disabled="<?= $activeReceipt ? 'false' : 'true' ?> || !selectedQuickPayDelivery()">Commit Collection</button>
                    <button class="btn btn-secondary" type="button" @click="closeQuickPay()">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
