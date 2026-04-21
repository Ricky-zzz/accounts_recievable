<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold">Payments</h1>
        <p class="mt-1 text-sm muted">Search for a client and start a payment.</p>
    </div>
    <form class="flex items-center gap-2" method="get" action="<?= base_url('payments') ?>">
        <input class="input" name="q" placeholder="Search client" value="<?= esc($query ?? '') ?>">
        <button class="btn btn-secondary" type="submit">Search</button>
    </form>
</div>

<table class="table mt-6">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th class="text-right">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($clients)): ?>
            <tr>
                <td class="py-3" colspan="4">No clients found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= esc($client['name']) ?></td>
                    <td><?= esc($client['email'] ?? '') ?></td>
                    <td><?= esc($client['phone'] ?? '') ?></td>
                    <td class="text-left">
                        <a class="btn-link" href="<?= base_url('payments/client/' . $client['id']) ?>">Pay</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>