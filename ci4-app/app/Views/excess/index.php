<?php
/**
 * @var list<array{pr_no?: int|string|null, date?: string|null, amount_received?: int|float|string|null, amount_allocated?: int|float|string|null, excess?: int|float|string|null, client_name?: string|null}> $rows
 * @var string $query
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold">Excess Payments</h1>
        <p class="mt-1 text-sm muted">Shows unallocated or used excess per payment.</p>
    </div>
    <form class="flex items-center gap-2" method="get" action="<?= base_url('excess') ?>">
        <input class="input" name="q" placeholder="Search client" value="<?= esc($query ?? '') ?>">
        <button class="btn btn-secondary" type="submit">Search</button>
    </form>
</div>

<table class="table mt-6">
    <thead>
        <tr>
            <th>#</th>
            <th>Client</th>
            <th>PR#</th>
            <th>Date</th>
            <th>Amount Received</th>
            <th>Allocated</th>
            <th>Excess</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr>
                <td class="py-3" colspan="7">No excess records.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= esc((string) ($index + 1)) ?></td>
                    <td><?= esc((string) ($row['client_name'] ?? '')) ?></td>
                    <td><?= esc((string) $row['pr_no']) ?></td>
                    <td><?= esc((string) $row['date']) ?></td>
                    <td><?= esc(number_format((float) $row['amount_received'], 2)) ?></td>
                    <td><?= esc(number_format((float) $row['amount_allocated'], 2)) ?></td>
                    <td><?= esc(number_format((float) $row['excess'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
