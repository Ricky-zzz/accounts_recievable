<?php
/**
 * @var list<array{id: int|string, bank_name: string, account_name?: string|null, bank_number?: string|null}> $banks
 * @var string|null $layout
 * @var string|null $basePath
 */
?>
<?= $this->extend($layout ?? 'layout') ?>
<?= $this->section('content') ?>
<?php
$formMode = (string) (session()->getFlashdata('form_mode') ?? '');
$formId = (int) (session()->getFlashdata('form_id') ?? 0);
$oldForm = [
    'bank_name' => old('bank_name'),
    'account_name' => old('account_name'),
    'bank_number' => old('bank_number'),
];

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$basePath = trim((string) ($basePath ?? 'banks'), '/');
?>

<div class="space-y-6" x-data="bankManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Banks</h1>
        <button class="btn" type="button" @click="openCreate()">New Bank</button>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Bank Name</th>
                <th>Account Name</th>
                <th>Account Number</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($banks)): ?>
                <tr>
                    <td colspan="5">No banks yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($banks as $index => $bank): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $bank['bank_name']) ?></td>
                        <td><?= esc((string) ($bank['account_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($bank['bank_number'] ?? '')) ?></td>
                        <td class="text-left">
                            <button class="btn-link" type="button" @click="openEdit(<?= (int) $bank['id'] ?>)">Edit</button>
                            <form class="inline" method="post" action="<?= base_url($basePath . '/' . $bank['id'] . '/delete') ?>" onsubmit="return confirm('Delete this bank?');">
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
        <div class="modal-panel max-w-xl p-6">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Bank' : 'New Bank'"></h2>
                <button class="btn btn-secondary" type="button" @click="closeModal()">Close</button>
            </div>

            <form class="mt-4 space-y-4" method="post" :action="formAction">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium" for="bank_name">Bank Name</label>
                    <input class="input mt-1" id="bank_name" name="bank_name" x-model="form.bank_name" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="account_name">Account Name</label>
                    <input class="input mt-1" id="account_name" name="account_name" x-model="form.account_name">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="bank_number">Account Number</label>
                    <input class="input mt-1" id="bank_number" name="bank_number" x-model="form.bank_number">
                </div>
                <div class="flex gap-3">
                    <button class="btn" type="submit" x-text="isEdit ? 'Update Bank' : 'Create Bank'"></button>
                    <button class="btn btn-secondary" type="button" @click="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function bankManager() {
        const banks = <?= json_encode($banks, $jsonFlags) ?>;
        const oldForm = <?= json_encode($oldForm, $jsonFlags) ?>;
        const formMode = '<?= esc($formMode, 'js') ?>';
        const formId = <?= $formId ?>;
        const hasOldValues = Object.values(oldForm).some((value) => value !== null && String(value).trim() !== '');

        return {
            banks,
            open: false,
            isEdit: false,
            formAction: '<?= base_url($basePath) ?>',
            currentId: null,
            form: {
                bank_name: '',
                account_name: '',
                bank_number: '',
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
                this.currentId = null;
                this.formAction = '<?= base_url($basePath) ?>';
                this.form = {
                    bank_name: '',
                    account_name: '',
                    bank_number: '',
                };
                this.open = true;
            },
            openEdit(id) {
                const bank = this.banks.find((row) => Number(row.id) === Number(id));
                if (!bank) {
                    return;
                }

                this.isEdit = true;
                this.currentId = bank.id;
                this.formAction = `<?= base_url($basePath) ?>/${bank.id}`;
                this.form = {
                    bank_name: bank.bank_name || '',
                    account_name: bank.account_name || '',
                    bank_number: bank.bank_number || '',
                };
                this.open = true;
            },
            closeModal() {
                this.open = false;
            },
        };
    }
</script>
<?= $this->endSection() ?>
