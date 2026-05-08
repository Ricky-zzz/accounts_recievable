<div class="modal-backdrop" x-show="transactionDetailOpen('payment')" x-cloak @click.self="closeDetail('payment')">
    <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold">
                PR Summary for:
                <span x-text="transactionDetailRecord('payment', 'payment') ? (transactionDetailRecord('payment', 'payment').pr_no || '') : transactionDetailFallback('payment')"></span>
            </h2>
            <button class="btn btn-secondary" type="button" @click="closeDetail('payment')">Close</button>
        </div>

        <div class="mt-3 text-sm muted" x-show="transactionDetailLoading('payment')">Loading details...</div>
        <div class="mt-3 text-sm text-red-600" x-show="transactionDetailError('payment')" x-text="transactionDetailError('payment')"></div>

        <div class="modal-split mt-5">
            <div>
                <h3 class="text-sm font-semibold">DR Allocations</h3>
                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>DR #</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('payment')">
                            <tr><td class="py-3" colspan="3">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('payment') && transactionDetailRows('payment', 'allocations').length === 0">
                            <tr><td class="py-3" colspan="3">No allocations found.</td></tr>
                        </template>
                        <template x-for="(allocation, index) in transactionDetailRows('payment', 'allocations')" :key="index">
                            <tr>
                                <td x-text="allocation.dr_no || '-'"></td>
                                <td x-text="allocation.date || '-'"></td>
                                <td class="text-right" x-text="formatAmount(allocation.amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="font-semibold">Total</span>
                    <span x-text="formatAmount(transactionDetailSum('payment', 'allocations', 'amount'))"></span>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold">Other Accounts</h3>
                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>Account Title</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('payment')">
                            <tr><td class="py-3" colspan="2">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('payment') && paymentOtherAccountRows().length === 0">
                            <tr><td class="py-3" colspan="2">No other accounts found.</td></tr>
                        </template>
                        <template x-for="(item, index) in paymentOtherAccountRows()" :key="'other-' + index">
                            <tr>
                                <td x-text="item.account_title || '-'"></td>
                                <td class="text-right" x-text="formatAmount(item.dr || item.ar_others || 0)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="mt-3 rounded border border-gray-200 p-4 text-sm" x-show="paymentArOtherRow()" x-cloak>
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold">A/R Other Description</span>
                        <span x-text="paymentArOtherRow() ? (paymentArOtherRow().description || '-') : '-'"></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span class="font-semibold">A/R Other Amount</span>
                        <span x-text="paymentArOtherRow() ? formatAmount(paymentArOtherRow().ar_others) : '0.00'"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
            <div class="card p-3">
                <p class="muted">Original Amount Received</p>
                <p class="font-semibold" x-text="transactionDetailRecord('payment', 'payment') ? formatAmount(transactionDetailRecord('payment', 'payment').amount_received) : '0.00'"></p>
            </div>
            <div class="card p-3">
                <p class="muted">Allocated to DRs</p>
                <p class="font-semibold" x-text="formatAmount(transactionDetailSum('payment', 'allocations', 'amount'))"></p>
            </div>
        </div>
    </div>
</div>
