<?php
/**
 * @var list<array{id: int|string, account_code: string, name: string, type: string}> $accounts
 * @var string $query
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="space-y-6" x-data="accountManager()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Other Accounts</h1>
            <p class="mt-1 text-sm muted">Manage chart of accounts for DR and CR entries.</p>
        </div>
        <div class="flex items-center gap-3">
            <form class="filter-card flex items-center gap-2 rounded border border-gray-200 p-3" method="get" action="<?= base_url('other-accounts') ?>" x-data>
                <input class="input" name="q" placeholder="Search account" value="<?= esc($query ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
                <?php if (! empty($query)): ?>
                    <a class="btn btn-secondary" href="<?= base_url('other-accounts') ?>">Clear</a>
                <?php endif; ?>
            </form>
            <button class="btn btn-strong" type="button" @click="openModal()">New Account</button>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($accounts)): ?>
                <tr>
                    <td class="py-3" colspan="5">No accounts yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($accounts as $index => $account): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><code class="text-xs"><?= esc((string) $account['account_code']) ?></code></td>
                        <td><?= esc((string) $account['name']) ?></td>
                        <td>
                            <span class="status-chip">
                                <?= strtoupper($account['type']) ?>
                            </span>
                        </td>
                        <td class="text-left">
                            <button class="btn-link" type="button" @click="openModal(<?= (int) $account['id'] ?>)">Edit</button>
                            <button class="ml-3 btn-link" type="button" @click="deleteAccount(<?= (int) $account['id'] ?>, '<?= esc((string) $account['account_code']) ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Modal -->
    <div class="modal-backdrop" x-show="open" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold" x-text="isEdit ? 'Edit Account' : 'New Account'"></h2>
            <form class="mt-4 space-y-4" @submit.prevent="saveAccount()">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium" for="account_code">Account Code</label>
                    <input 
                        class="input mt-1" 
                        id="account_code" 
                        x-model="form.account_code" 
                        required
                        placeholder="e.g., 1000">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="name">Name</label>
                    <input 
                        class="input mt-1" 
                        id="name" 
                        x-model="form.name" 
                        required
                        placeholder="e.g., Sales Revenue">
                </div>
                <div>
                    <label class="block text-sm font-medium" for="type">Type</label>
                    <select 
                        class="input mt-1" 
                        id="type" 
                        x-model="form.type" 
                        required>
                        <option value="">Select type...</option>
                        <option value="dr">DR</option>
                        <option value="cr">CR</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button class="btn btn-strong" type="submit" :disabled="loading">
                        <span x-show="!loading" x-text="isEdit ? 'Update' : 'Create'"></span>
                        <span x-show="loading">Saving...</span>
                    </button>
                    <button class="btn btn-secondary" type="button" @click="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function accountManager() {
        return {
            open: false,
            isEdit: false,
            loading: false,
            form: {
                account_code: '',
                name: '',
                type: 'dr',
            },
            currentId: null,

            openModal(id = null) {
                this.isEdit = !!id;
                this.currentId = id;

                if (id) {
                    this.loading = true;
                    fetch(`<?= base_url('other-accounts') ?>/${id}/get`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.form = {
                                    account_code: data.data.account_code,
                                    name: data.data.name,
                                    type: data.data.type,
                                };
                            }
                        })
                        .finally(() => {
                            this.loading = false;
                            this.open = true;
                        });
                } else {
                    this.form = {
                        account_code: '',
                        name: '',
                        type: 'dr',
                    };
                    this.open = true;
                }
            },

            closeModal() {
                this.open = false;
                this.form = {
                    account_code: '',
                    name: '',
                    type: 'dr',
                };
                this.currentId = null;
            },

            async saveAccount() {
                this.loading = true;

                const url = this.isEdit 
                    ? `<?= base_url('other-accounts') ?>/${this.currentId}` 
                    : `<?= base_url('other-accounts') ?>`;
                const method = this.isEdit ? 'POST' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new URLSearchParams({
                            '_method': this.isEdit ? 'PUT' : 'POST',
                            'account_code': this.form.account_code,
                            'name': this.form.name,
                            'type': this.form.type,
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                        }),
                    });

                    const data = await response.json();

                    if (!data.success) {
                        window.showToast(this.errorMessage(data), 'error');
                    } else {
                        this.closeModal();
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error:', error);
                } finally {
                    this.loading = false;
                }
            },

            errorMessage(data) {
                if (data.message) {
                    return data.message;
                }

                const errors = Object.values(data.errors || {});
                return errors.length ? errors.join(' ') : 'Please check the form and try again.';
            },

            async deleteAccount(id, code) {
                if (!confirm(`Delete account "${code}"?`)) return;

                try {
                    const response = await fetch(`<?= base_url('other-accounts') ?>/${id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new URLSearchParams({
                            '_method': 'DELETE',
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        location.reload();
                    } else {
                        window.showToast(this.errorMessage(data), 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            },
        };
    }
</script>
<?= $this->endSection() ?>
