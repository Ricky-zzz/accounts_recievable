<div class="modal-backdrop" x-show="transactionDetailOpen('purchaseOrder')" x-cloak @click.self="closeDetail('purchaseOrder')">
    <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
        <div class="mb-4 flex items-start justify-between gap-4 border-b pb-4">
            <div>
                <h2 class="text-lg font-semibold">
                    RR Details:
                    <span x-text="transactionDetailRecord('purchaseOrder', 'purchase_order') ? (transactionDetailRecord('purchaseOrder', 'purchase_order').po_no || '') : transactionDetailFallback('purchaseOrder')"></span>
                </h2>
                <p class="mt-1 text-sm muted" x-text="transactionDetailRecord('purchaseOrder', 'purchase_order') ? (transactionDetailRecord('purchaseOrder', 'purchase_order').supplier_name || '') : ''"></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeDetail('purchaseOrder')">Close</button>
        </div>

        <div class="mb-4 text-sm muted" x-show="transactionDetailLoading('purchaseOrder')">Loading details...</div>
        <div class="mb-4 text-sm text-red-600" x-show="transactionDetailError('purchaseOrder')" x-text="transactionDetailError('purchaseOrder')"></div>

        <div class="modal-split">
            <div>
                <h3 class="mb-3 font-semibold">Pickup Items</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                            <th>Source PO</th>
                            <th class="text-right">PO Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('purchaseOrder')">
                            <tr><td class="py-3 text-center" colspan="6">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('purchaseOrder') && transactionDetailRows('purchaseOrder', 'items').length === 0">
                            <tr><td class="py-3 text-center" colspan="6">No items found.</td></tr>
                        </template>
                        <template x-for="item in transactionDetailRows('purchaseOrder', 'items')" :key="item.id">
                            <tr>
                                <td x-text="item.product_name || '-'"></td>
                                <td class="text-right" x-text="formatQty(item.qty)"></td>
                                <td class="text-right" x-text="formatAmount(item.unit_price)"></td>
                                <td class="text-right" x-text="formatAmount(item.line_total)"></td>
                                <td x-text="item.supplier_order_po_no || '-'"></td>
                                <td class="text-right" x-text="item.po_qty_balance_after !== null && item.po_qty_balance_after !== undefined ? formatQty(item.po_qty_balance_after) : '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div>
                <h3 class="mb-3 font-semibold">CV Allocations</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>CV #</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('purchaseOrder')">
                            <tr><td class="py-3 text-center" colspan="3">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('purchaseOrder') && transactionDetailRows('purchaseOrder', 'allocations').length === 0">
                            <tr><td class="py-3 text-center" colspan="3">No allocations found.</td></tr>
                        </template>
                        <template x-for="(allocation, index) in transactionDetailRows('purchaseOrder', 'allocations')" :key="index">
                            <tr>
                                <td x-text="allocation.pr_no || '-'"></td>
                                <td x-text="allocation.date || '-'"></td>
                                <td class="text-right" x-text="formatAmount(allocation.amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6" x-show="transactionDetailRows('purchaseOrder', 'delivery_allocations').length > 0" x-cloak>
            <h3 class="mb-3 font-semibold">Connected DRs</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>DR#</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Product</th>
                        <th class="text-right">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in transactionDetailRows('purchaseOrder', 'delivery_allocations')" :key="index">
                        <tr>
                            <td x-text="item.dr_no || '-'"></td>
                            <td x-text="item.date || '-'"></td>
                            <td x-text="item.client_name || '-'"></td>
                            <td x-text="item.product_name || '-'"></td>
                            <td class="text-right" x-text="formatQty(item.qty_allocated)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
