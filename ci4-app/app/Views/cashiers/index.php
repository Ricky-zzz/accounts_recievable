<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="space-y-6" x-data="cashierRange()">
    <div>
        <h1 class="text-xl font-semibold">Cashiers</h1>
        <p class="mt-1 text-sm muted">Create cashiers and assign receipt ranges.</p>
    </div>

    <form class="card p-4" method="post" action="<?= base_url('cashiers') ?>">
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium" for="name">Name</label>
                <input class="input mt-1" id="name" name="name" value="<?= esc(old('name')) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium" for="username">User</label>
                <input class="input mt-1" id="username" name="username" value="<?= esc(old('username')) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium" for="password">Password</label>
                <input class="input mt-1" id="password" name="password" type="password" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn" type="submit">Add Cashier</button>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>User</th>
                <th>Receipt</th>
                <th>Active Receipt</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cashiers)): ?>
                <tr>
                    <td class="py-3" colspan="4">No cashiers yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($cashiers as $cashier): ?>
                    <?php
                    $range = $activeRanges[$cashier['id']] ?? null;
                    $hasActive = $range && (int) $range['next_no'] <= (int) $range['end_no'];
                    ?>
                    <tr>
                        <td><?= esc($cashier['name']) ?></td>
                        <td><?= esc($cashier['username']) ?></td>
                        <td>
                            <?php if ($hasActive): ?>
                                <?= esc($range['start_no']) ?> - <?= esc($range['end_no']) ?>
                            <?php else: ?>
                                <button class="btn btn-secondary" type="button" @click="openAssign(<?= (int) $cashier['id'] ?>, '<?= esc($cashier['name']) ?>')">Assign</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $hasActive ? esc($range['next_no']) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="modal-backdrop" x-show="open" x-cloak>
        <div class="modal-panel max-w-md p-6">
            <h2 class="text-lg font-semibold">Assign Receipt Range</h2>
            <p class="mt-1 text-sm muted" x-text="cashierName"></p>
            <form class="mt-4 space-y-4" method="post" action="<?= base_url('cashiers/assign-range') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="cashier_id" :value="cashierId">
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
                    <button class="btn" type="submit">Save Range</button>
                    <button class="btn btn-secondary" type="button" @click="closeAssign()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function cashierRange() {
        return {
            open: false,
            cashierId: null,
            cashierName: '',
            openAssign(id, name) {
                this.cashierId = id;
                this.cashierName = name;
                this.open = true;
            },
            closeAssign() {
                this.open = false;
                this.cashierId = null;
                this.cashierName = '';
            }
        };
    }
</script>
<?= $this->endSection() ?>
