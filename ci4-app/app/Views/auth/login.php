<?php
/**
 * @var object|null $validation
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="mx-auto flex min-h-[70vh] max-w-md items-center">
    <div class="card w-full px-6 py-8">
        <div class="text-center">
            <img class="mx-auto h-20 w-20 rounded-2xl border border-stone-200 object-cover bg-white" src="<?= esc(base_url('logo.png')) ?>" alt="SRC Enterprises logo">
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">SRC Enterprises Inc</p>
            <h1 class="mt-2 text-2xl font-semibold">Sign In</h1>
            <p class="mt-2 text-sm muted">Enter your username and password to continue.</p>
        </div>

        <?php if (isset($validation)): ?>
            <div class="card mt-6 px-4 py-2 text-sm">
                <ul class="list-disc pl-5">
                    <?php foreach ($validation->getErrors() as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" method="post" action="<?= base_url('login') ?>">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium" for="username">Username</label>
                <input class="input mt-1" id="username" name="username" value="<?= esc(old('username')) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium" for="password">Password</label>
                <input class="input mt-1" id="password" name="password" type="password" required>
            </div>
            <button class="btn w-full" type="submit">Login</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
