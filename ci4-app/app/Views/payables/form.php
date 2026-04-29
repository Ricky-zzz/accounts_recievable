<?php
/**
 * @var array{id: int|string, name: string} $supplier
 * @var array{id?: int|string, name?: string|null}|null $assignedUser
 * @var int|null $activeReceipt
 * @var int|null $rangeEnd
 * @var list<array{id: int|string, bank_name: string}> $banks
 * @var list<array{purchase_order_id: int|string, po_no?: string|null, date?: string|null, due_date?: string|null, total_amount?: int|float|string|null, allocated_amount?: int|float|string|null, balance?: int|float|string|null}> $unpaidPurchaseOrders
 * @var string $unpaidPagerLinks
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$ordersJson = json_encode($unpaidPurchaseOrders ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$cashierLabel = trim((string) (($assignedUser['name'] ?? '') . ' (' . ($assignedUser['username'] ?? '-') . ')'));
?>

<div x-data="payableForm()" data-orders="<?= esc($ordersJson, 'attr') ?>" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Pay Supplier: <?= esc((string) $supplier['name']) ?></h1>
            <p class="mt-1 text-sm muted">Allocate payment to unpaid purchase orders.</p>
        </div>
        <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . $supplier['id']) ?>">Back</a>
    </div>

    <?php if (! $activeReceipt): ?>
        <div class="card p-4"><p class="text-sm text-red-700">No active receipt range is assigned to your user yet. Ask admin to assign a range before posting payables.</p></div>
    <?php endif; ?>

    <form class="space-y-6" method="post" action="<?= base_url('payables') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="supplier_id" value="<?= esc((string) $supplier['id']) ?>">

        <div class="card p-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div><label class="block text-sm font-medium">Cashier</label><input class="input mt-1" type="text" value="<?= esc($cashierLabel) ?>" readonly></div>
                <div><label class="block text-sm font-medium">Active PR</label><input class="input mt-1" type="text" value="<?= $activeReceipt ? esc((string) $activeReceipt) : 'Not assigned' ?>" readonly><?php if ($activeReceipt && $rangeEnd): ?><p class="mt-1 text-xs muted">Range end: <?= esc((string) $rangeEnd) ?></p><?php endif; ?></div>
                <div><label class="block text-sm font-medium" for="date">Date</label><input class="input mt-1" id="date" name="date" type="date" value="<?= esc(old('date') ?: date('Y-m-d')) ?>" required></div>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div><label class="block text-sm font-medium" for="method">Payment Method</label><select class="input mt-1" id="method" name="method" x-model="method" required><option value="cash">Cash</option><option value="bank">Bank</option><option value="check">Check</option></select></div>
                <div><label class="block text-sm font-medium" for="amount_received">Amount Paid</label><input class="input mt-1" id="amount_received" name="amount_received" type="number" step="0.01" min="0" x-model="amountReceived" required></div>
                <div><label class="block text-sm font-medium" for="deposit_bank_id">Bank</label><select class="input mt-1" id="deposit_bank_id" name="deposit_bank_id" required><option value="">Select bank</option><?php foreach ($banks as $bank): ?><option value="<?= esc((string) $bank['id']) ?>" <?= (string) old('deposit_bank_id') === (string) $bank['id'] ? 'selected' : '' ?>><?= esc((string) $bank['bank_name']) ?></option><?php endforeach; ?></select></div>
                <div x-show="method === 'bank' || method === 'check'" x-cloak><label class="block text-sm font-medium" for="payer_bank">Supplier Bank</label><input class="input mt-1" id="payer_bank" name="payer_bank" value="<?= esc(old('payer_bank')) ?>" :disabled="method === 'cash'"></div>
                <div x-show="method === 'check'" x-cloak><label class="block text-sm font-medium" for="check_no">Check Number</label><input class="input mt-1" id="check_no" name="check_no" value="<?= esc(old('check_no')) ?>" :disabled="method !== 'check'"></div>
            </div>
        </div>

        <div class="overflow-x-auto pb-2">
            <div class="flex min-w-[1040px] gap-6">
                <div class="card w-[440px] shrink-0 p-4">
                    <div class="flex items-center justify-between"><h2 class="text-sm font-semibold">Unpaid Purchase Orders</h2><span class="text-xs muted" x-text="visibleOrders.length + ' items'"></span></div>
                    <table class="table mt-4">
                        <thead><tr><th>PO#</th><th>Date</th><th>Due Date</th><th>Balance</th><th class="text-right">Pay</th></tr></thead>
                        <tbody>
                            <template x-if="visibleOrders.length === 0"><tr><td colspan="5">No unpaid purchase orders.</td></tr></template>
                            <template x-for="order in visibleOrders" :key="order.purchase_order_id"><tr><td x-text="order.po_no"></td><td x-text="order.date"></td><td x-text="order.due_date || '-'"></td><td x-text="formatAmount(order.working_balance)"></td><td class="text-right"><button class="btn btn-secondary" type="button" @click="openPayModal(order)" :disabled="Number(order.working_balance) <= 0">Pay</button></td></tr></template>
                        </tbody>
                    </table>
                    <div class="mt-3 flex items-center justify-between text-sm"><span class="font-semibold">Total Balance</span><span class="font-semibold" x-text="formatAmount(visibleOrdersTotal())"></span></div>
                    <?php if (! empty($unpaidPagerLinks)): ?><div class="mt-4 flex justify-end"><?= $unpaidPagerLinks ?></div><?php endif; ?>
                </div>

                <div class="card w-[360px] shrink-0 p-4">
                    <h2 class="text-sm font-semibold">Allocations</h2>
                    <table class="table mt-4">
                        <thead><tr><th>PO#</th><th>Amount</th><th class="text-right">x</th></tr></thead>
                        <tbody>
                            <template x-if="allocations.length === 0"><tr><td colspan="3">No allocations yet.</td></tr></template>
                            <template x-for="(allocation, index) in allocations" :key="allocation.purchase_order_id + '-' + index"><tr><td x-text="allocation.po_no"></td><td x-text="formatAmount(allocation.amount)"></td><td><button class="btn-link" type="button" @click="removeAllocation(index)">x</button><input type="hidden" :name="'allocations[' + index + '][purchase_order_id]'" :value="allocation.purchase_order_id"><input type="hidden" :name="'allocations[' + index + '][amount]'" :value="allocation.amount"></td></tr></template>
                        </tbody>
                    </table>
                </div>

                <div class="card w-[260px] shrink-0 p-4">
                    <h2 class="text-sm font-semibold">Other Accounts</h2>
                    <div class="mt-4 grid gap-4">
                        <div><label class="block text-sm font-medium" for="sales_discount">Purchase Discount</label><input class="input mt-1" id="sales_discount" name="sales_discount" type="number" step="0.01" min="0" x-model="salesDiscount"></div>
                        <div><label class="block text-sm font-medium" for="delivery_charges">Delivery Charges</label><input class="input mt-1" id="delivery_charges" name="delivery_charges" type="number" step="0.01" min="0" x-model="deliveryCharges"></div>
                        <div><label class="block text-sm font-medium" for="taxes">Taxes</label><input class="input mt-1" id="taxes" name="taxes" type="number" step="0.01" min="0" x-model="taxes"></div>
                        <div><label class="block text-sm font-medium" for="commissions">Commissions</label><input class="input mt-1" id="commissions" name="commissions" type="number" step="0.01" min="0" x-model="commissions"></div>
                    </div>
                </div>

                <?php /* A/P Other is hidden until the payable-side accounting treatment is confirmed. */ ?>
            </div>
        </div>

        <div class="card p-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div><p class="muted">Amount Paid</p><p class="font-semibold" x-text="formatAmount(amountReceived)"></p></div>
                <div><p class="muted">Allocated Total</p><p class="font-semibold" x-text="formatAmount(allocatedTotal())"></p></div>
                <div><p class="muted">Other Accounts Total</p><p class="font-semibold" x-text="formatAmount(fixedAccountsTotal())"></p></div>
                <div><p class="muted">Unallocated</p><p class="font-semibold" x-text="formatAmount(balanceAmount())"></p></div>
            </div>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <button class="btn" type="submit" <?= $activeReceipt ? '' : 'disabled' ?>>Commit Payable</button>
                <a class="btn btn-secondary" href="<?= base_url('payables/supplier/' . $supplier['id']) ?>">Cancel</a>
            </div>
        </div>
    </form>

    <div class="modal-backdrop" x-show="modalOpen" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold">Allocate Payable</h2>
            <p class="mt-1 text-sm muted" x-text="modalOrder ? 'PO# ' + modalOrder.po_no : ''"></p>
            <div class="mt-4 space-y-3">
                <div class="flex justify-between text-sm"><span>Date</span><span x-text="modalOrder ? modalOrder.date : ''"></span></div>
                <div class="flex justify-between text-sm"><span>Balance</span><span x-text="modalOrder ? formatAmount(modalOrder.working_balance) : ''"></span></div>
                <div><label class="block text-sm font-medium" for="pay_amount">Amount to Pay</label><input class="input mt-1" id="pay_amount" type="number" step="0.01" min="0" x-model="modalAmount"></div>
            </div>
            <div class="mt-4 flex gap-3"><button class="btn" type="button" @click="confirmAllocation()">OK</button><button class="btn btn-secondary" type="button" @click="closePayModal()">Cancel</button></div>
        </div>
    </div>
</div>

<script>
    function payableForm() {
        return {
            orders: [],
            allocations: [],
            method: '<?= esc(old('method') ?: 'cash') ?>',
            amountReceived: '<?= esc(old('amount_received')) ?>',
            arOtherDescription: '',
            arOtherAmount: '0',
            salesDiscount: '<?= esc(old('sales_discount')) ?>',
            deliveryCharges: '<?= esc(old('delivery_charges')) ?>',
            taxes: '<?= esc(old('taxes')) ?>',
            commissions: '<?= esc(old('commissions')) ?>',
            modalOpen: false,
            modalOrder: null,
            modalAmount: '',
            init() {
                const orders = this.parseJson(this.$el.dataset.orders, []);
                this.orders = orders.map((order) => ({ ...order, working_balance: this.normalizeAmount(order.balance) }));
            },
            get visibleOrders() { return this.orders.filter((order) => Number(order.working_balance) > 0); },
            normalizeAmount(value) { return Math.round((parseFloat(value) || 0) * 100) / 100; },
            formatAmount(value) { return this.normalizeAmount(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            parseJson(value, fallback) { try { return value ? JSON.parse(value) : fallback; } catch (error) { return fallback; } },
            visibleOrdersTotal() { return this.visibleOrders.reduce((sum, order) => sum + (parseFloat(order.working_balance) || 0), 0); },
            allocatedTotal() { return this.allocations.reduce((sum, allocation) => sum + (parseFloat(allocation.amount) || 0), 0); },
            fixedAccountsTotal() { return [this.salesDiscount, this.deliveryCharges, this.taxes, this.commissions].reduce((sum, value) => sum + (parseFloat(value) || 0), 0); },
            balanceAmount() { return (parseFloat(this.amountReceived) || 0) + this.fixedAccountsTotal() - this.allocatedTotal(); },
            openPayModal(order) { this.modalOrder = order; this.modalAmount = ''; this.modalOpen = true; },
            closePayModal() { this.modalOpen = false; this.modalOrder = null; this.modalAmount = ''; },
            confirmAllocation() {
                const amount = parseFloat(this.modalAmount) || 0;
                if (!this.modalOrder || amount <= 0) return;
                if (amount > this.modalOrder.working_balance) { alert('Amount exceeds purchase order balance.'); return; }
                this.modalOrder.working_balance = this.normalizeAmount(this.modalOrder.working_balance - amount);
                this.allocations.push({ purchase_order_id: this.modalOrder.purchase_order_id, po_no: this.modalOrder.po_no, amount: amount, balance_after: this.modalOrder.working_balance });
                this.closePayModal();
            },
            removeAllocation(index) {
                const allocation = this.allocations[index];
                if (!allocation) return;
                const order = this.orders.find((row) => String(row.purchase_order_id) === String(allocation.purchase_order_id));
                if (order) order.working_balance = this.normalizeAmount(order.working_balance + parseFloat(allocation.amount));
                this.allocations.splice(index, 1);
            },
        };
    }
</script>
<?= $this->endSection() ?>
