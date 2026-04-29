<?php
/**
 * @var list<array{id: int|string, name: string, address?: string|null, email?: string|null, phone?: string|null, credit_limit?: int|float|string|null, payment_term?: int|string|null, current_balance?: int|float|string|null, available_credit?: int|float|string|null}> $suppliers
 * @var string $query
 * @var \CodeIgniter\Pager\Pager|null $pager
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$formMode = (string) (session()->getFlashdata('form_mode') ?? '');
$formId = (int) (session()->getFlashdata('form_id') ?? 0);
$oldForm = [
    'name' => old('name'),
    'address' => old('address'),
    'email' => old('email'),
    'phone' => old('phone'),
    'credit_limit' => old('credit_limit'),
    'payment_term' => old('payment_term'),
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>

<div class="space-y-6" x-data="supplierManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Suppliers</h1>
        <div class="flex items-center gap-3">
            <form class="flex items-center gap-2" method="get" action="<?= base_url('suppliers') ?>">
                <input class="input" name="q" placeholder="Search supplier" value="<?= esc($query ?? '') ?>">
                <button class="btn btn-secondary" type="submit">Search</button>
                <a class="btn btn-secondary" href="<?= base_url('suppliers') ?>">Clear</a>
            </form>
            <button class="btn" type="button" @click="openCreate()">New Supplier</button>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Available Credit</th>
                <th>Transactions</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="7">No suppliers yet.</td></tr>
            <?php else: ?>
                <?php foreach ($suppliers as $index => $supplier): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $supplier['name']) ?></td>
                        <td><?= esc((string) ($supplier['email'] ?? '')) ?></td>
                        <td><?= esc((string) ($supplier['phone'] ?? '')) ?></td>
                        <td>
                            <button class="btn-link" type="button" @click="openCreditModal(<?= (int) $supplier['id'] ?>)">
                                <?= esc(number_format((float) ($supplier['available_credit'] ?? 0), 2)) ?>
                            </button>
                        </td>
                        <td>
                            <a class="btn-link" href="<?= base_url('payable-ledger?supplier_id=' . $supplier['id']) ?>">Ledger</a> |
                            <a class="btn-link" href="<?= base_url('suppliers/' . $supplier['id'] . '/purchase-orders') ?>">Orders</a> |
                            <a class="btn-link" href="<?= base_url('payables/supplier/' . $supplier['id']) ?>">Payables</a>
                        </td>
                        <td class="text-left">
                            <button class="btn-link text-green-950" type="button" @click="openEdit(<?= (int) $supplier['id'] ?>)">Edit</button>
                            <form class="inline" method="post" action="<?= base_url('suppliers/' . $supplier['id'] . '/delete') ?>" onsubmit="return confirm('Delete this supplier?');">
                                <?= csrf_field() ?>
                                <button class="ml-3 btn-link" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (isset($pager)): ?>
        <div class="flex justify-end"><?= $pager->links() ?></div>
    <?php endif; ?>

    <div class="modal-backdrop" x-show="open" x-cloak @click.self="closeModal()">
        <div class="modal-panel max-w-2xl p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Supplier' : 'New Supplier'"></h2>
                <button class="btn btn-secondary" type="button" @click="closeModal()">Close</button>
            </div>
            <form class="mt-4 space-y-4" method="post" :action="formAction">
                <?= csrf_field() ?>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium" for="name">Name</label>
                        <input class="input mt-1" id="name" name="name" x-model="form.name" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium" for="address">Address</label>
                        <input class="input mt-1" id="address" name="address" x-model="form.address">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="email">Email</label>
                        <input class="input mt-1" id="email" name="email" x-model="form.email">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="phone">Phone</label>
                        <input class="input mt-1" id="phone" name="phone" x-model="form.phone">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="credit_limit">Credit Limit</label>
                        <input class="input mt-1" id="credit_limit" name="credit_limit" type="number" step="0.01" min="0" x-model="form.credit_limit">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="payment_term">Payment Term (days)</label>
                        <input class="input mt-1" id="payment_term" name="payment_term" type="number" step="1" min="0" x-model="form.payment_term">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="btn" type="submit" x-text="isEdit ? 'Update Supplier' : 'Create Supplier'"></button>
                    <button class="btn btn-secondary" type="button" @click="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" x-show="openCredit" x-cloak @click.self="closeCreditModal()">
        <div class="modal-panel max-w-md p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold">Available Credit</h2>
                <button class="btn btn-secondary" type="button" @click="closeCreditModal()">Close</button>
            </div>
            <div class="mt-4 space-y-2 text-sm">
                <p class="font-medium" x-text="selectedCredit.name"></p>
                <p>Credit Limit: <span class="font-medium" x-text="formatAmount(selectedCredit.credit_limit)"></span></p>
                <p>Current Balance: <span class="font-medium" x-text="formatAmount(selectedCredit.current_balance)"></span></p>
                <p>Available Credit: <span class="font-medium" x-text="formatAmount(selectedCredit.available_credit)"></span></p>
            </div>
        </div>
    </div>
</div>

<script>
    function supplierManager() {
        const suppliers = <?= json_encode($suppliers, $jsonFlags) ?>;
        const oldForm = <?= json_encode($oldForm, $jsonFlags) ?>;
        const formMode = '<?= esc($formMode, 'js') ?>';
        const formId = <?= $formId ?>;
        const hasOldValues = Object.values(oldForm).some((value) => value !== null && String(value).trim() !== '');
        return {
            suppliers,
            open: false,
            openCredit: false,
            isEdit: false,
            formAction: '<?= base_url('suppliers') ?>',
            selectedCredit: { name: '', credit_limit: 0, current_balance: 0, available_credit: 0 },
            form: { name: '', address: '', email: '', phone: '', credit_limit: '', payment_term: '' },
            init() {
                if (formMode === 'edit' && formId > 0) {
                    this.openEdit(formId);
                    if (hasOldValues) this.form = { ...this.form, ...oldForm };
                    this.open = true;
                    return;
                }
                if (formMode === 'create') {
                    this.openCreate();
                    this.form = { ...this.form, ...oldForm };
                    this.open = true;
                }
            },
            openCreate() {
                this.isEdit = false;
                this.formAction = '<?= base_url('suppliers') ?>';
                this.form = { name: '', address: '', email: '', phone: '', credit_limit: '', payment_term: '' };
                this.open = true;
            },
            openEdit(id) {
                const supplier = this.suppliers.find((row) => Number(row.id) === Number(id));
                if (!supplier) return;
                this.isEdit = true;
                this.formAction = `<?= base_url('suppliers') ?>/${supplier.id}`;
                this.form = {
                    name: supplier.name || '',
                    address: supplier.address || '',
                    email: supplier.email || '',
                    phone: supplier.phone || '',
                    credit_limit: supplier.credit_limit || '',
                    payment_term: supplier.payment_term || '',
                };
                this.open = true;
            },
            closeModal() { this.open = false; },
            openCreditModal(id) {
                const supplier = this.suppliers.find((row) => Number(row.id) === Number(id));
                if (!supplier) return;
                this.selectedCredit = {
                    name: supplier.name || '',
                    credit_limit: Number(supplier.credit_limit || 0),
                    current_balance: Number(supplier.current_balance || 0),
                    available_credit: Number(supplier.available_credit || 0),
                };
                this.openCredit = true;
            },
            closeCreditModal() { this.openCredit = false; },
            formatAmount(value) {
                return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
<?= $this->endSection() ?>
