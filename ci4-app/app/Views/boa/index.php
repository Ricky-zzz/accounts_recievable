<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold">BOA</h1>
        <p class="mt-1 text-sm muted">Filter BOA records by date range.</p>
    </div>
    <form class="flex flex-wrap items-end gap-3" method="get" action="<?= base_url('boa') ?>">
        <div>
            <label class="block text-sm font-medium">From</label>
            <input class="input" type="date" name="from" value="<?= esc($from) ?>">
        </div>
        <div>
            <label class="block text-sm font-medium">To</label>
            <input class="input" type="date" name="to" value="<?= esc($to) ?>">
        </div>
        <button class="btn btn-secondary" type="submit">Filter</button>
        <a class="btn" target="_blank" href="<?= base_url('boa/print') ?>?from=<?= esc($from) ?>&to=<?= esc($to) ?>">Print PDF</a>
    </form>
</div>

<?php if ($tableMissing): ?>
    <div class="card mt-6 px-4 py-2 text-sm">
        BOA table is missing. Run migrations first.
    </div>
<?php else: ?>
    <table class="table mt-6">
        <thead>
            <tr>
                <th>Date</th>
                <th>Payor</th>
                <th>Reference</th>
                <?php foreach ($bankColumns as $column): ?>
                    <th class="text-right"><?= esc($column) ?></th>
                <?php endforeach; ?>
                <th class="text-right">AR Trade</th>
                <th class="text-right">AR Other</th>
                <th>Account Title</th>
                <th class="text-right">DR</th>
                <th class="text-right">CR</th>
                <th>Note</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td class="py-3" colspan="<?= 10 + count($bankColumns) ?>">No BOA records in this range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= esc($row['date']) ?></td>
                        <td><?= esc($row['payor_name'] ?? $row['payor']) ?></td>
                        <td><?= esc($row['reference']) ?></td>
                        <?php foreach ($bankColumns as $column): ?>
                            <td class="text-left"><?= number_format((float) ($row[$column] ?? 0), 2) ?></td>
                        <?php endforeach; ?>
                        <td class="text-left"><?= number_format((float) ($row['ar_trade'] ?? 0), 2) ?></td>
                        <td class="text-left"><?= number_format((float) ($row['ar_others'] ?? 0), 2) ?></td>
                        <td><?= esc($row['account_title'] ?? '') ?></td>
                        <td class="text-left"><?= number_format((float) ($row['dr'] ?? 0), 2) ?></td>
                        <td class="text-left"><?= number_format((float) ($row['cr'] ?? 0), 2) ?></td>
                        <td><?= esc($row['note'] ?? '') ?></td>
                        <td><?= esc($row['description'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($records)): ?>
            <tfoot>
                <tr class="border-t border-gray-300 font-semibold">
                    <td>Totals:</td>
                    <td></td>
                    <td></td>
                    <?php foreach ($bankColumns as $column): ?>
                        <td class="text-left"><?= number_format((float) ($totals['bankColumns'][$column] ?? 0), 2) ?></td>
                    <?php endforeach; ?>
                    <td class="text-left"><?= number_format((float) ($totals['ar_trade'] ?? 0), 2) ?></td>
                    <td class="text-left"><?= number_format((float) ($totals['ar_others'] ?? 0), 2) ?></td>
                    <td></td>
                    <td class="text-left"><?= number_format((float) ($totals['dr'] ?? 0), 2) ?></td>
                    <td class="text-left"><?= number_format((float) ($totals['cr'] ?? 0), 2) ?></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
<?php endif; ?>
<?= $this->endSection() ?>