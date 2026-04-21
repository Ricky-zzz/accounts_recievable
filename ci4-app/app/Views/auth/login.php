<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h1 class="text-xl font-semibold">Admin Login</h1>
<p class="mt-1 text-sm muted">Enter your username and password to continue.</p>

<?php if (isset($validation)): ?>
    <div class="card mt-4 px-4 py-2 text-sm">
        <ul class="list-disc pl-5">
            <?php foreach ($validation->getErrors() as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="mt-6 max-w-md space-y-4" method="post" action="<?= base_url('login') ?>">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium" for="username">Username</label>
        <input class="input mt-1" id="username" name="username" value="<?= esc(old('username')) ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="password">Password</label>
        <input class="input mt-1" id="password" name="password" type="password" required>
    </div>
    <button class="btn" type="submit">Login</button>
</form>
<?= $this->endSection() ?>