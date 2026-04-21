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
        <label class="block text-sm font-medium" for="name">Name</label>
        <input class="input mt-1" id="name" name="name" value="<?= esc($client['name']) ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="address">Address</label>
        <input class="input mt-1" id="address" name="address" value="<?= esc($client['address']) ?>">
    </div>
    <div>
        <label class="block text-sm font-medium" for="email">Email</label>
        <input class="input mt-1" id="email" name="email" value="<?= esc($client['email']) ?>">
    </div>
    <div>
        <label class="block text-sm font-medium" for="phone">Phone</label>
        <input class="input mt-1" id="phone" name="phone" value="<?= esc($client['phone']) ?>">
    </div>
    <div class="flex gap-3">
        <button class="btn" type="submit">Save</button>
        <a class="btn btn-secondary" href="<?= base_url('clients') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>