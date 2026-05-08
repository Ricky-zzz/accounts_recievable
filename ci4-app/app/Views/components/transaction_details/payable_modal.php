<div class="modal-backdrop" x-show="transactionDetailOpen('payable')" x-cloak @click.self="closeDetail('payable')">
    <div class="modal-panel max-h-[92vh] max-w-5xl overflow-y-auto p-6" @click.stop>
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold">
                CV Details
                <span x-text="transactionDetailRecord('payable', 'payable') ? (transactionDetailRecord('payable', 'payable').pr_no || '') : transactionDetailFallback('payable')"></span>
            </h2>
            <button class="btn btn-secondary" type="button" @click="closeDetail('payable')">Close</button>
        </div>

        <div class="mt-4 text-sm muted" x-show="transactionDetailLoading('payable')">Loading details...</div>
        <div class="mt-4 text-sm text-red-600" x-show="transactionDetailError('payable')" x-text="transactionDetailError('payable')"></div>

        <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
            <div class="card p-3">
                <p class="muted">Amount Paid</p>
                <p class="font-semibold" x-text="transactionDetailRecord('payable', 'payable') ? formatAmount(transactionDetailRecord('payable', 'payable').amount_received) : '0.00'"></p>
            </div>
            <div class="card p-3">
                <p class="muted">Allocated to RRs</p>
                <p class="font-semibold" x-text="formatAmount(transactionDetailSum('payable', 'allocations', 'amount'))"></p>
            </div>
            <div class="card p-3">
                <p class="muted">Other Accounts</p>
                <p class="font-semibold" x-text="formatAmount(transactionDetailSum('payable', 'other_accounts', 'other_accounts'))"></p>
            </div>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <h3 class="text-sm font-semibold">RR Allocations</h3>
                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>RR #</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('payable')">
                            <tr><td colspan="3">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('payable') && transactionDetailRows('payable', 'allocations').length === 0">
                            <tr><td colspan="3">No allocations found.</td></tr>
                        </template>
                        <template x-for="(allocation, index) in transactionDetailRows('payable', 'allocations')" :key="index">
                            <tr>
                                <td x-text="allocation.po_no || '-'"></td>
                                <td x-text="allocation.date || '-'"></td>
                                <td class="text-right" x-text="formatAmount(allocation.amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
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
                        <template x-if="transactionDetailLoading('payable')">
                            <tr><td colspan="2">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('payable') && transactionDetailRows('payable', 'other_accounts').length === 0">
                            <tr><td colspan="2">No other accounts found.</td></tr>
                        </template>
                        <template x-for="(item, index) in transactionDetailRows('payable', 'other_accounts')" :key="index">
                            <tr>
                                <td x-text="item.account_title || '-'"></td>
                                <td class="text-right" x-text="formatAmount(item.other_accounts || 0)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
