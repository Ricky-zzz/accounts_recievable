<?php
/**
 * @var list<array{id: int|string, name: string, address?: string|null, email?: string|null, phone?: string|null, credit_limit?: int|float|string|null, payment_term?: int|string|null, current_balance?: int|float|string|null, available_credit?: int|float|string|null}> $clients
 * @var string $query
 * @var \CodeIgniter\Pager\Pager|null $pager
 */
?>
<?= $this->extend('layout') ?>
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
$defaultSoaStart = date('Y-m-01');
$defaultSoaEnd = date('Y-m-t');
?>

<div class="space-y-6" x-data="clientManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Clients</h1>
        <div class="flex items-center gap-3">
            <form class="flex items-center gap-2" method="get" action="<?= base_url('clients') ?>">
                <input class="input" name="q" placeholder="Search client" value="<?= esc($query ?? '') ?>">
                <button class="btn btn-secondary" type="submit">Search</button>
                <a class="btn btn-secondary" href="<?= base_url('clients') ?>">Clear</a>
            </form>
            <button class="btn" type="button" @click="openCreate()">New Client</button>
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
            <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="7">No clients yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clients as $index => $client): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $client['name']) ?></td>
                        <td><?= esc((string) ($client['email'] ?? '')) ?></td>
                        <td><?= esc((string) ($client['phone'] ?? '')) ?></td>
                        <td>
                            <?php
                            $availableCredit = (float) ($client['available_credit'] ?? 0);
                            ?>
                            <button
                                class="btn-link"
                                type="button"
                                @click="openCreditModal(<?= (int) $client['id'] ?>)"
                            >
                                <?= esc(number_format($availableCredit, 2)) ?>
                            </button>
                        </td>
                        <td>
                            <a class="btn-link" href="<?= base_url('ledger?client_id=' . $client['id']) ?>">Ledger</a> |
                            <a class="btn-link" href="<?= base_url('clients/' . $client['id'] . '/deliveries') ?>">Deliveries</a> |
                            <a class="btn-link" href="<?= base_url('payments/client/' . $client['id']) ?>">Collections</a> |
                            <button class="btn-link" type="button" @click="openSoaModal(<?= (int) $client['id'] ?>, '<?= esc((string) $client['name'], 'js') ?>')">SOA</button>
                        </td>
                        <td class="text-left">
                            <button class="btn-link text-green-950" type="button" @click="openEdit(<?= (int) $client['id'] ?>)">Edit</button>
                            <form class="inline" method="post" action="<?= base_url('clients/' . $client['id'] . '/delete') ?>" onsubmit="return confirm('Delete this client?');">
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
        <div class="flex justify-end">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>

    <div class="modal-backdrop" x-show="open" x-cloak @click.self="closeModal()">
        <div class="modal-panel max-w-2xl p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Client' : 'New Client'"></h2>
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
                        <label class="block text-sm font-medium" for="payment_term">Default Collection Term (days)</label>
                        <input class="input mt-1" id="payment_term" name="payment_term" type="number" step="1" min="0" x-model="form.payment_term">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="btn" type="submit" x-text="isEdit ? 'Update Client' : 'Create Client'"></button>
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
                <p>
                    Credit Limit:
                    <span class="font-medium" x-text="formatAmount(selectedCredit.credit_limit)"></span>
                </p>
                <p>
                    Current Balance:
                    <span class="font-medium" x-text="formatAmount(selectedCredit.current_balance)"></span>
                </p>
                <p>
                    Available Credit:
                    <span class="font-medium" x-text="formatAmount(selectedCredit.available_credit)"></span>
                </p>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" x-show="openSoa" x-cloak @click.self="closeSoaModal()">
        <div class="modal-panel max-w-md p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold" x-text="`SOA for ${soaClientName}`"></h2>
                <button class="btn btn-secondary" type="button" @click="closeSoaModal()">Close</button>
            </div>

            <div class="mt-4 space-y-3 text-sm">
                <div>
                    <label class="block text-sm font-medium" for="soa_start">Billing From</label>
                    <input class="input mt-1" id="soa_start" type="date" x-model="soaStart">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="soa_end">Billing To</label>
                    <input class="input mt-1" id="soa_end" type="date" x-model="soaEnd">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="soa_due">Due Date</label>
                    <input class="input mt-1" id="soa_due" type="date" x-model="soaDueDate">
                </div>
            </div>

            <div class="mt-5 flex gap-3">
                <button class="btn" type="button" @click="openSoaPrint()">Preview SOA</button>
                <button class="btn btn-secondary" type="button" @click="closeSoaModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    function clientManager() {
        const clients = <?= json_encode($clients, $jsonFlags) ?>;
        const oldForm = <?= json_encode($oldForm, $jsonFlags) ?>;
        const formMode = '<?= esc($formMode, 'js') ?>';
        const formId = <?= $formId ?>;
        const hasOldValues = Object.values(oldForm).some((value) => value !== null && String(value).trim() !== '');
        const baseUrl = '<?= rtrim(base_url(), '/') ?>';
        const defaultSoaStart = '<?= esc($defaultSoaStart, 'js') ?>';
        const defaultSoaEnd = '<?= esc($defaultSoaEnd, 'js') ?>';

        return {
            clients,
            open: false,
            openCredit: false,
            openSoa: false,
            isEdit: false,
            formAction: '<?= base_url('clients') ?>',
            soaClientId: null,
            soaClientName: '',
            soaStart: defaultSoaStart,
            soaEnd: defaultSoaEnd,
            soaDueDate: '',
            selectedCredit: {
                name: '',
                credit_limit: 0,
                current_balance: 0,
                available_credit: 0,
            },
            form: {
                name: '',
                address: '',
                email: '',
                phone: '',
                credit_limit: '',
                payment_term: '',
            },
            init() {
                if (formMode === 'edit' && formId > 0) {
                    this.openEdit(formId);
                    if (hasOldValues) {
                        this.form = {
                            ...this.form,
                            ...oldForm,
                        };
                    }
                    this.open = true;
                    return;
                }

                if (formMode === 'create') {
                    this.openCreate();
                    this.form = {
                        ...this.form,
                        ...oldForm,
                    };
                    this.open = true;
                }
            },
            openCreate() {
                this.isEdit = false;
                this.formAction = '<?= base_url('clients') ?>';
                this.form = {
                    name: '',
                    address: '',
                    email: '',
                    phone: '',
                    credit_limit: '',
                    payment_term: '',
                };
                this.open = true;
            },
            openEdit(id) {
                const client = this.clients.find((row) => Number(row.id) === Number(id));
                if (!client) {
                    return;
                }

                this.isEdit = true;
                this.formAction = `<?= base_url('clients') ?>/${client.id}`;
                this.form = {
                    name: client.name || '',
                    address: client.address || '',
                    email: client.email || '',
                    phone: client.phone || '',
                    credit_limit: client.credit_limit || '',
                    payment_term: client.payment_term || '',
                };
                this.open = true;
            },
            closeModal() {
                this.open = false;
            },
            openCreditModal(id) {
                const client = this.clients.find((row) => Number(row.id) === Number(id));
                if (!client) {
                    return;
                }

                this.selectedCredit = {
                    name: client.name || '',
                    credit_limit: Number(client.credit_limit || 0),
                    current_balance: Number(client.current_balance || 0),
                    available_credit: Number(client.available_credit || 0),
                };
                this.openCredit = true;
            },
            closeCreditModal() {
                this.openCredit = false;
            },
            openSoaModal(id, name) {
                this.soaClientId = id;
                this.soaClientName = name || '';
                this.soaStart = defaultSoaStart;
                this.soaEnd = defaultSoaEnd;
                this.soaDueDate = '';
                this.openSoa = true;
            },
            closeSoaModal() {
                this.openSoa = false;
            },
            openSoaPrint() {
                if (!this.soaClientId) {
                    return;
                }
                const params = new URLSearchParams({
                    start: this.soaStart || '',
                    end: this.soaEnd || '',
                    due_date: this.soaDueDate || '',
                });
                const url = `${baseUrl}/clients/${this.soaClientId}/soa?${params.toString()}`;
                window.open(url, '_blank');
                this.openSoa = false;
            },
            formatAmount(value) {
                const number = Number(value || 0);
                return number.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
        };
    }
</script>
<?= $this->endSection() ?>
