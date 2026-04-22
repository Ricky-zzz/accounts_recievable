<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<!-- Tab Navigation -->
<div class="mb-6 flex border-b border-gray-300">
    <a 
        href="<?= base_url('deliveries') ?>" 
        class="tab-link tab-link-active">
        New Delivery
    </a>
    <a 
        href="<?= base_url('deliveries/list') ?>" 
        class="tab-link">
        View Deliveries
    </a>
</div>

<div>
    <h2 class="text-lg font-semibold">Search Client</h2>
    <p class="mt-1 text-sm muted">Find a client to create a new delivery.</p>
</div>
<form class="mt-4 flex items-center gap-2" method="get" action="<?= base_url('deliveries') ?>">
    <input class="input" name="q" placeholder="Search client" value="<?= esc($query ?? '') ?>">
    <button class="btn btn-secondary" type="submit">Search</button>
</form>

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
                <td class="py-3" colspan="4">
                    <?php if ($query === ''): ?>
                        Enter a client name to search.
                    <?php else: ?>
                        No clients found.
                    <?php endif; ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= esc($client['name']) ?></td>
                    <td><?= esc($client['email'] ?? '') ?></td>
                    <td><?= esc($client['phone'] ?? '') ?></td>
                    <td class="text-left">
                        <a class="btn-link" href="<?= base_url('deliveries/client/' . $client['id']) ?>">New Delivery</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?= $this->endSection() ?>