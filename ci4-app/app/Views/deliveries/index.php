<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-xl font-semibold">Deliveries</h1>
        <p class="mt-1 text-sm muted">Delivery receipts and totals.</p>
    </div>
    <a class="btn" href="<?= base_url('deliveries/new') ?>">New Delivery</a>
</div>

<table class="table mt-6">
    <thead>
        <tr>
            <th>Date</th>
            <th>DR#</th>
            <th>Client</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($deliveries)): ?>
            <tr>
                <td class="py-3" colspan="4">No deliveries yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($deliveries as $delivery): ?>
                <tr>
                    <td><?= esc($delivery['date']) ?></td>
                    <td><?= esc($delivery['dr_no']) ?></td>
                    <td><?= esc($delivery['client_name'] ?? '') ?></td>
                    <td><?= esc(number_format((float) $delivery['total_amount'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>