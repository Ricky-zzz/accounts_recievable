<div class="modal-backdrop" x-show="transactionDetailOpen('supplierOrder')" x-cloak @click.self="closeDetail('supplierOrder')">
    <div class="modal-panel max-h-[92vh] max-w-6xl overflow-y-auto p-6" @click.stop>
        <div class="mb-5 flex items-start justify-between gap-4 border-b pb-4">
            <div>
                <h2 class="text-lg font-semibold">
                    PO Details:
                    <span x-text="transactionDetailRecord('supplierOrder', 'supplier_order') ? (transactionDetailRecord('supplierOrder', 'supplier_order').po_no || '') : transactionDetailFallback('supplierOrder')"></span>
                </h2>
                <p class="mt-1 text-sm muted" x-text="transactionDetailRecord('supplierOrder', 'supplier_order') ? (transactionDetailRecord('supplierOrder', 'supplier_order').supplier_name || '') : ''"></p>
            </div>
            <button class="btn btn-secondary" type="button" @click="closeDetail('supplierOrder')">Close</button>
        </div>

        <div class="mb-4 text-sm muted" x-show="transactionDetailLoading('supplierOrder')">Loading details...</div>
        <div class="mb-4 text-sm text-red-600" x-show="transactionDetailError('supplierOrder')" x-text="transactionDetailError('supplierOrder')"></div>

        <div class="space-y-5">
            <section>
                <h3 class="mb-3 text-sm font-semibold">PO Items</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-right">Purchase Qty</th>
                            <th class="text-right">Picked-Up</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="transactionDetailLoading('supplierOrder')">
                            <tr><td colspan="4">Loading...</td></tr>
                        </template>
                        <template x-if="!transactionDetailLoading('supplierOrder') && transactionDetailRows('supplierOrder', 'items').length === 0">
                            <tr><td colspan="4">No items found.</td></tr>
                        </template>
                        <template x-for="item in transactionDetailRows('supplierOrder', 'items')" :key="item.id">
                            <tr>
                                <td x-text="item.product_name || '-'"></td>
                                <td class="text-right" x-text="formatQty(item.qty_ordered)"></td>
                                <td class="text-right" x-text="formatQty(item.qty_picked_up)"></td>
                                <td class="text-right" x-text="formatQty(item.qty_balance)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </section>

            <section>
                <h3 class="mb-3 text-sm font-semibold">RR Consumption</h3>
                <div class="overflow-y-auto rounded border border-gray-200" style="max-height: 45vh;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>RR#</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="transactionDetailLoading('supplierOrder')">
                                <tr><td colspan="4">Loading...</td></tr>
                            </template>
                            <template x-if="!transactionDetailLoading('supplierOrder') && transactionDetailRows('supplierOrder', 'consumptions').length === 0">
                                <tr><td colspan="4">No RR consumption found.</td></tr>
                            </template>
                            <template x-for="(item, index) in transactionDetailRows('supplierOrder', 'consumptions')" :key="index">
                                <tr>
                                    <td>
                                        <button class="btn-link" type="button" @click="openDetail('purchaseOrder', item.purchase_order_id, item.rr_no || '')" x-text="item.rr_no || '-'"></button>
                                    </td>
                                    <td x-text="item.rr_date || '-'"></td>
                                    <td x-text="item.product_name || '-'"></td>
                                    <td class="text-right" x-text="formatQty(item.qty)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
