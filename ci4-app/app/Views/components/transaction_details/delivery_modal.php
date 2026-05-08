<div class="modal-backdrop" x-show="transactionDetailOpen('delivery')" x-cloak @click.self="closeDetail('delivery')">
    <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
        <div class="mb-4 flex items-start justify-between gap-4 border-b pb-4">
            <div>
                <h2 class="text-lg font-semibold">
                    DR Details:
                    <span x-text="transactionDetailRecord('delivery', 'delivery') ? (transactionDetailRecord('delivery', 'delivery').dr_no || '') : transactionDetailFallback('delivery')"></span>
                </h2>
                <p class="mt-1 text-sm muted" x-text="transactionDetailRecord('delivery', 'delivery') ? (transactionDetailRecord('delivery', 'delivery').client_name || '') : ''"></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeDetail('delivery')">Close</button>
        </div>

        <div class="mb-4 text-sm muted" x-show="transactionDetailLoading('delivery')">Loading details...</div>
        <div class="mb-4 text-sm text-red-600" x-show="transactionDetailError('delivery')" x-text="transactionDetailError('delivery')"></div>

        <div class="modal-split">
            <div>
                <h3 class="mb-3 font-semibold">Delivery Items</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('delivery')">
                            <tr><td class="py-3 text-center" colspan="4">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('delivery') && transactionDetailRows('delivery', 'items').length === 0">
                            <tr><td class="py-3 text-center" colspan="4">No items found.</td></tr>
                        </template>
                        <template x-for="item in transactionDetailRows('delivery', 'items')" :key="item.id">
                            <tr>
                                <td x-text="item.product_name || '-'"></td>
                                <td class="text-right" x-text="formatQty(item.qty)"></td>
                                <td class="text-right" x-text="formatAmount(item.unit_price)"></td>
                                <td class="text-right" x-text="formatAmount(item.line_total)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="mt-2 text-sm font-semibold" x-show="transactionDetailRows('delivery', 'items').length > 0">
                    Total: <span x-text="formatAmount(transactionDetailSum('delivery', 'items', 'line_total'))"></span>
                </div>
            </div>

            <div>
                <h3 class="mb-3 font-semibold">DR Allocations</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>PR #</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('delivery')">
                            <tr><td class="py-3 text-center" colspan="3">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('delivery') && transactionDetailRows('delivery', 'allocations').length === 0">
                            <tr><td class="py-3 text-center" colspan="3">No allocations found.</td></tr>
                        </template>
                        <template x-for="(allocation, index) in transactionDetailRows('delivery', 'allocations')" :key="index">
                            <tr>
                                <td x-text="allocation.pr_no || '-'"></td>
                                <td x-text="allocation.date || '-'"></td>
                                <td class="text-right" x-text="formatAmount(allocation.amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="mt-2 text-sm font-semibold" x-show="transactionDetailRows('delivery', 'allocations').length > 0">
                    Total: <span x-text="formatAmount(transactionDetailSum('delivery', 'allocations', 'amount'))"></span>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="mb-3 font-semibold">Connected RR / Pickup</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>RR#</th>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Deliverable / Loss</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="transactionDetailLoading('delivery')">
                        <tr><td colspan="5">Loading...</td></tr>
                    </template>
                    <template x-if="!transactionDetailLoading('delivery') && transactionDetailRows('delivery', 'pickup_allocations').length === 0">
                        <tr><td colspan="5">No RR connected.</td></tr>
                    </template>
                    <template x-for="item in transactionDetailRows('delivery', 'pickup_allocations')" :key="String(item.purchase_order_id || '') + '-' + String(item.product_id || '')">
                        <tr>
                            <td x-text="item.rr_no || '-'"></td>
                            <td x-text="item.supplier_name || '-'"></td>
                            <td x-text="item.product_name || '-'"></td>
                            <td class="text-right" x-text="formatQty(item.qty_allocated)"></td>
                            <td class="text-right" x-text="formatQty((parseFloat(item.remaining_qty) || 0) - (parseFloat(item.qty_allocated) || 0))"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
