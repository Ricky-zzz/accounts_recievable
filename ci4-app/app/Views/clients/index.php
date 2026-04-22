<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$formErrors = session()->getFlashdata('form_errors');
if (! is_array($formErrors)) {
    $formErrors = [];
}

$formMode = (string) (session()->getFlashdata('form_mode') ?? '');
$formId = (int) (session()->getFlashdata('form_id') ?? 0);
$oldForm = [
    'name' => old('name'),
    'address' => old('address'),
    'email' => old('email'),
    'phone' => old('phone'),
];

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>

<div class="space-y-6" x-data="clientManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Clients</h1>
        <div class="flex items-center gap-3">
            <form class="flex items-center gap-2" method="get" action="<?= base_url('clients') ?>">
                <input class="input" name="q" placeholder="Search client" value="<?= esc($query ?? '') ?>">
                <button class="btn btn-secondary" type="submit">Search</button>
            </form>
            <button class="btn" type="button" @click="openCreate()">New Client</button>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Ledger</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="5">No clients yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= esc($client['name']) ?></td>
                        <td><?= esc($client['email'] ?? '') ?></td>
                        <td><?= esc($client['phone'] ?? '') ?></td>
                        <td>
                            <a class="btn-link" href="<?= base_url('ledger?client_id=' . $client['id']) ?>">View Ledger</a>
                        </td>
                        <td class="text-left">
                            <button class="btn-link" type="button" @click="openEdit(<?= (int) $client['id'] ?>)">Edit</button>
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

    <div class="modal-backdrop" x-show="open" x-cloak>
        <div class="modal-panel max-w-2xl p-6">
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
                        <span class="field-error" x-show="errors.name" x-text="errors.name"></span>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium" for="address">Address</label>
                        <input class="input mt-1" id="address" name="address" x-model="form.address">
                        <span class="field-error" x-show="errors.address" x-text="errors.address"></span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="email">Email</label>
                        <input class="input mt-1" id="email" name="email" x-model="form.email">
                        <span class="field-error" x-show="errors.email" x-text="errors.email"></span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="phone">Phone</label>
                        <input class="input mt-1" id="phone" name="phone" x-model="form.phone">
                        <span class="field-error" x-show="errors.phone" x-text="errors.phone"></span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="btn" type="submit" x-text="isEdit ? 'Update Client' : 'Create Client'"></button>
                    <button class="btn btn-secondary" type="button" @click="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function clientManager() {
        const clients = <?= json_encode($clients, $jsonFlags) ?>;
        const oldForm = <?= json_encode($oldForm, $jsonFlags) ?>;
        const formErrors = <?= json_encode($formErrors, $jsonFlags) ?>;
        const formMode = '<?= esc($formMode, 'js') ?>';
        const formId = <?= $formId ?>;
        const hasOldValues = Object.values(oldForm).some((value) => value !== null && String(value).trim() !== '');

        return {
            clients,
            open: false,
            isEdit: false,
            formAction: '<?= base_url('clients') ?>',
            errors: {},
            form: {
                name: '',
                address: '',
                email: '',
                phone: '',
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
                    this.errors = formErrors;
                    this.open = true;
                    return;
                }

                if (formMode === 'create') {
                    this.openCreate();
                    this.form = {
                        ...this.form,
                        ...oldForm,
                    };
                    this.errors = formErrors;
                    this.open = true;
                }
            },
            openCreate() {
                this.isEdit = false;
                this.formAction = '<?= base_url('clients') ?>';
                this.errors = {};
                this.form = {
                    name: '',
                    address: '',
                    email: '',
                    phone: '',
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
                this.errors = {};
                this.form = {
                    name: client.name || '',
                    address: client.address || '',
                    email: client.email || '',
                    phone: client.phone || '',
                };
                this.open = true;
            },
            closeModal() {
                this.open = false;
                this.errors = {};
            },
        };
    }
</script>
<?= $this->endSection() ?>