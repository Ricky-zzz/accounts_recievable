<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Banks</h1>
    <a class="btn" href="<?= base_url('banks/new') ?>">New Bank</a>
</div>

<table class="table mt-6">
    <thead>
        <tr>
            <th>Bank Name</th>
            <th>Account Name</th>
            <th>Account Number</th>
            <th class="text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($banks)): ?>
            <tr>
                <td class="py-3" colspan="4">No banks yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($banks as $bank): ?>
                <tr>
                    <td><?= esc($bank['bank_name']) ?></td>
                    <td><?= esc($bank['account_name'] ?? '') ?></td>
                    <td><?= esc($bank['bank_number'] ?? '') ?></td>
                    <td class="text-left">
                        <a class="btn-link" href="<?= base_url('banks/' . $bank['id'] . '/edit') ?>">Edit</a>
                        <form class="inline" method="post" action="<?= base_url('banks/' . $bank['id'] . '/delete') ?>" onsubmit="return confirm('Delete this bank?');">
                            <?= csrf_field() ?>
                            <button class="ml-3 btn-link" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>