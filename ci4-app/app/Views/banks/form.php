<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h1 class="text-xl font-semibold"><?= esc($title) ?></h1>

<?php if (isset($validation)): ?>
    <div class="card mt-4 px-4 py-2 text-sm">
        <ul class="list-disc pl-5">
            <?php foreach ($validation->getErrors() as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="mt-6 max-w-xl space-y-4" method="post" action="<?= esc($action) ?>">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium" for="bank_name">Bank Name</label>
        <input class="input mt-1" id="bank_name" name="bank_name" value="<?= esc($bank['bank_name']) ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="account_name">Account Name</label>
        <input class="input mt-1" id="account_name" name="account_name" value="<?= esc($bank['account_name']) ?>">
    </div>
    <div>
        <label class="block text-sm font-medium" for="bank_number">Account Number</label>
        <input class="input mt-1" id="bank_number" name="bank_number" value="<?= esc($bank['bank_number']) ?>">
    </div>
    <div class="flex gap-3">
        <button class="btn" type="submit">Save</button>
        <a class="btn btn-secondary" href="<?= base_url('banks') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>