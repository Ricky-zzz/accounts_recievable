<?php
/**
 * @var list<array{id: int|string, name: string, username: string, type?: string|null}> $cashiers
 * @var array<int|string, array{start_no?: int|string, end_no?: int|string, next_no?: int|string}> $activeRanges
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
    'username' => old('username'),
    'password' => '',
];

$sessionUserId = (int) (session('user_id') ?? 0);
$sessionUserType = (string) (session('type') ?? '');

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>

<div class="space-y-6" x-data="cashierManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Users and Receipt Ranges</h1>
            <p class="mt-1 text-sm muted">Manage cashiers and assign receipt ranges.</p>
        </div>
        <button class="btn btn-strong" type="button" @click="openCreate()">New Cashier</button>
    </div>

    <form method="get" action="<?= base_url('cashiers') ?>" class="card p-4" x-data>
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full max-w-md">
                <label class="block text-sm font-medium" for="q">Search Cashier</label>
                <input class="input mt-1" id="q" name="q" value="<?= esc($query ?? '') ?>" placeholder="Search by name or username" @input.debounce.1000ms="$el.form.requestSubmit()">
            </div>
            <?php if (! empty($query)): ?>
                <a class="btn btn-secondary" href="<?= base_url('cashiers') ?>">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>User</th>
                <th>Type</th>
                <th>Receipt</th>
                <th>Active Receipt</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cashiers)): ?>
                <tr>
                    <td class="py-3" colspan="7">No users found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($cashiers as $index => $cashier): ?>
                    <?php
                    $range = $activeRanges[$cashier['id']] ?? null;
                    $hasActive = $range && (int) $range['next_no'] <= (int) $range['end_no'];
                    $isCashier = ($cashier['type'] ?? '') === 'cashier';
                    $isCurrentAdmin = $sessionUserType === 'admin'
                        && (int) $cashier['id'] === $sessionUserId
                        && ($cashier['type'] ?? '') === 'admin';
                    $canAssignReceipt = $isCashier || $isCurrentAdmin;
                    ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $cashier['name']) ?></td>
                        <td><?= esc((string) $cashier['username']) ?></td>
                        <td><?= esc(ucfirst($cashier['type'] ?? 'cashier')) ?></td>
                        <td>
                            <?php if ($hasActive): ?>
                                <?= esc((string) $range['start_no']) ?> - <?= esc((string) $range['end_no']) ?>
                            <?php elseif ($canAssignReceipt): ?>
                                <button class="btn btn-secondary" type="button" @click="openAssign(<?= (int) $cashier['id'] ?>)">Assign</button>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $hasActive ? esc((string) $range['next_no']) : '-' ?>
                        </td>
                        <td class="text-left">
                            <?php if ($isCashier): ?>
                                <button class="btn-link" type="button" @click="openEdit(<?= (int) $cashier['id'] ?>)">Edit</button>
                                <form class="inline" method="post" action="<?= base_url('cashiers/' . $cashier['id'] . '/delete') ?>" onsubmit="return confirm('Delete this cashier?');">
                                    <?= csrf_field() ?>
                                    <button class="ml-3 btn-link" type="submit">Delete</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
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

    <div class="modal-backdrop" x-show="openForm" x-cloak>
        <div class="modal-panel max-w-xl p-6">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Cashier' : 'New Cashier'"></h2>
                <button class="btn btn-secondary" type="button" @click="closeForm()">Close</button>
            </div>

            <form class="mt-4 space-y-4" method="post" :action="formAction">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium" for="name">Name</label>
                    <input class="input mt-1" id="name" name="name" x-model="form.name" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="username">User</label>
                    <input class="input mt-1" id="username" name="username" x-model="form.username" required>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="password">Password <span class="muted" x-show="isEdit">(leave blank to keep current password)</span></label>
                    <input class="input mt-1" id="password" name="password" type="password" :required="!isEdit">
                </div>
                <div class="flex gap-3">
                    <button class="btn btn-strong" type="submit" x-text="isEdit ? 'Update Cashier' : 'Create Cashier'"></button>
                    <button class="btn btn-secondary" type="button" @click="closeForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" x-show="openAssignModal" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold">Assign Receipt Range</h2>
            <p class="mt-1 text-sm muted" x-text="cashierName"></p>
            <form class="mt-4 space-y-4" method="post" action="<?= base_url('cashiers/assign-range') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" :value="cashierId">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="start_no">From</label>
                        <input class="input mt-1" id="start_no" name="start_no" type="number" min="1" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="end_no">To</label>
                        <input class="input mt-1" id="end_no" name="end_no" type="number" min="1" required>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="btn btn-strong" type="submit">Save Range</button>
                    <button class="btn btn-secondary" type="button" @click="closeAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function cashierManager() {
        const cashiers = <?= json_encode($cashiers, $jsonFlags) ?>;
        const oldForm = <?= json_encode($oldForm, $jsonFlags) ?>;
        const formMode = '<?= esc($formMode, 'js') ?>';
        const formId = <?= $formId ?>;
        const sessionUserId = <?= $sessionUserId ?>;
        const sessionUserType = '<?= esc($sessionUserType, 'js') ?>';
        const hasOldValues = Object.values(oldForm).some((value) => value !== null && String(value).trim() !== '');

        return {
            cashiers,
            openForm: false,
            openAssignModal: false,
            isEdit: false,
            formAction: '<?= base_url('cashiers') ?>',
            form: {
                name: '',
                username: '',
                password: '',
            },
            cashierId: null,
            cashierName: '',

            init() {
                if (formMode === 'edit' && formId > 0) {
                    this.openEdit(formId);
                    if (hasOldValues) {
                        this.form = {
                            ...this.form,
                            ...oldForm,
                            password: '',
                        };
                    }
                    this.openForm = true;
                    return;
                }

                if (formMode === 'create') {
                    this.openCreate();
                    this.form = {
                        ...this.form,
                        ...oldForm,
                        password: '',
                    };
                    this.openForm = true;
                }
            },
            openCreate() {
                this.isEdit = false;
                this.formAction = '<?= base_url('cashiers') ?>';
                this.form = {
                    name: '',
                    username: '',
                    password: '',
                };
                this.openForm = true;
            },
            openEdit(id) {
                const cashier = this.cashiers.find((row) => Number(row.id) === Number(id));
                if (!cashier || cashier.type !== 'cashier') {
                    return;
                }

                this.isEdit = true;
                this.formAction = `<?= base_url('cashiers') ?>/${cashier.id}`;
                this.form = {
                    name: cashier.name || '',
                    username: cashier.username || '',
                    password: '',
                };
                this.openForm = true;
            },
            closeForm() {
                this.openForm = false;
            },
            openAssign(id) {
                const cashier = this.cashiers.find((row) => Number(row.id) === Number(id));
                const isCashier = cashier && cashier.type === 'cashier';
                const isCurrentAdmin = cashier
                    && cashier.type === 'admin'
                    && Number(cashier.id) === Number(sessionUserId)
                    && sessionUserType === 'admin';

                if (!cashier || (!isCashier && !isCurrentAdmin)) {
                    return;
                }

                this.cashierId = id;
                this.cashierName = cashier.name || '';
                this.openAssignModal = true;
            },
            closeAssignModal() {
                this.openAssignModal = false;
                this.cashierId = null;
                this.cashierName = '';
            }
        };
    }
</script>
<?= $this->endSection() ?>
