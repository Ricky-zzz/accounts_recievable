<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-xl font-semibold">Clients</h1>
    <div class="flex items-center gap-3">
        <form class="flex items-center gap-2" method="get" action="<?= base_url('clients') ?>">
            <input class="input" name="q" placeholder="Search client" value="<?= esc($query ?? '') ?>">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>
        <a class="btn" href="<?= base_url('clients/new') ?>">New Client</a>
    </div>
</div>

<table class="table mt-6">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Ledger</th>
            <th class="text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($clients)): ?>
            <tr>
                <td class="py-3" colspan="5">No clients yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= esc($client['name']) ?></td>
                    <td><?= esc($client['email'] ?? '') ?></td>
                    <td><?= esc($client['phone'] ?? '') ?></td>
                    <td>
                        <a class="btn-link" href="<?= base_url('ledger?client_id=' . $client['id']) ?>">View Ledger</a>
                    </td>
                    <td class="text-left">
                        <a class="btn-link" href="<?= base_url('clients/' . $client['id'] . '/edit') ?>">Edit</a>
                        <form class="inline" method="post" action="<?= base_url('clients/' . $client['id'] . '/delete') ?>" onsubmit="return confirm('Delete this client?');">
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