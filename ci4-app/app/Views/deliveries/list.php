<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<!-- Tab Navigation -->
<div class="mb-6 flex border-b border-gray-300">
    <a 
        href="<?= base_url('deliveries') ?>" 
        class="tab-link">
        New Delivery
    </a>
    <a 
        href="<?= base_url('deliveries/list') ?>" 
        class="tab-link tab-link-active">
        View Deliveries
    </a>
</div>

<div>
    <h2 class="text-lg font-semibold">Delivery History</h2>
    <p class="mt-1 text-sm muted">Filter deliveries by date range.</p>
</div>

<form method="get" action="<?= base_url('deliveries/list') ?>" class="mt-4 grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="from_date">From Date</label>
        <input 
            class="input mt-1" 
            id="from_date" 
            name="from_date" 
            type="date" 
            value="<?= esc($fromDate ?? '') ?>"
        >
    </div>
    <div>
        <label class="block text-sm font-medium" for="to_date">To Date</label>
        <input 
            class="input mt-1" 
            id="to_date" 
            name="to_date" 
            type="date" 
            value="<?= esc($toDate ?? '') ?>"
        >
    </div>
    <div class="flex items-end gap-2">
        <button class="btn btn-secondary" type="submit">Filter</button>
        <a class="btn btn-secondary" href="<?= base_url('deliveries/list') ?>">Clear</a>
    </div>
</form>

<table class="table mt-6">
    <thead>
        <tr>
            <th>Date</th>
            <th>DR #</th>
            <th>Client</th>
            <th>Total Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($deliveries)): ?>
            <tr>
                <td class="py-3" colspan="5">No deliveries found for the selected date range.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($deliveries as $delivery): ?>
                <tr>
                    <td><?= esc($delivery['date']) ?></td>
                    <td><?= esc($delivery['dr_no']) ?></td>
                    <td><?= esc($delivery['client_name'] ?? '') ?></td>
                    <td><?= esc(number_format((float) $delivery['total_amount'], 2)) ?></td>
                    <td>
                        <span class="status-chip">
                            <?= esc(ucfirst($delivery['status'])) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?= $this->endSection() ?>
