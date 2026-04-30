<?php
/**
 * @var string $from
 * @var string $to
 * @var list<array<string, int|float|string|null>> $records
 * @var list<string> $bankColumns
 * @var array{bankColumns?: array<string, int|float|string>, ar_trade?: int|float|string, ar_others?: int|float|string, dr?: int|float|string, cr?: int|float|string} $totals
 * @var bool $tableMissing
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold">BOA</h1>
        <p class="mt-1 text-sm muted">Filter BOA records by date range.</p>
    </div>
    <form class="filter-card flex flex-wrap items-end gap-3 rounded border border-gray-200 p-3" method="get" action="<?= base_url('boa') ?>" x-data>
        <div>
            <label class="block text-sm font-medium">From</label>
            <input class="input" type="date" name="from" value="<?= esc($from) ?>" @change="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium">To</label>
            <input class="input" type="date" name="to" value="<?= esc($to) ?>" @change="$el.form.requestSubmit()">
        </div>
        <a class="btn" target="_blank" href="<?= base_url('boa/print') ?>?from=<?= esc($from) ?>&to=<?= esc($to) ?>">Print</a>
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
                <th>#</th>
                <th>Date</th>
                <th>Payor</th>
                <th>Reference</th>
                <?php foreach ($bankColumns as $column): ?>
                    <th class="text-right"><?= esc($column) ?></th>
                <?php endforeach; ?>
                <th class="text-right">AR Trade</th>
                <th class="text-right">AR Other</th>
                <th>AR Other Description</th>
                <th>Account Title</th>
                <th class="text-right">DR</th>
                <th class="text-right">CR</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td class="py-3" colspan="<?= 10 + count($bankColumns) ?>">No BOA records in this range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $row['date']) ?></td>
                        <td><?= esc((string) ($row['payor_name'] ?? $row['payor'])) ?></td>
                        <td><?= esc((string) $row['reference']) ?></td>
                        <?php foreach ($bankColumns as $column): ?>
                            <td class="text-left"><?= number_format((float) ($row[$column] ?? 0), 2) ?></td>
                        <?php endforeach; ?>
                        <td class="text-left"><?= number_format((float) ($row['ar_trade'] ?? 0), 2) ?></td>
                        <td class="text-left"><?= number_format((float) ($row['ar_others'] ?? 0), 2) ?></td>
                        <td><?= esc((string) ($row['description'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['account_title'] ?? '')) ?></td>
                        <td class="text-left"><?= number_format((float) ($row['dr'] ?? 0), 2) ?></td>
                        <td class="text-left"><?= number_format((float) ($row['cr'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($records)): ?>
            <tfoot>
                <tr class="border-t border-gray-300 total-highlight">
                    <td>Totals:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <?php foreach ($bankColumns as $column): ?>
                        <td class="text-left"><?= number_format((float) ($totals['bankColumns'][$column] ?? 0), 2) ?></td>
                    <?php endforeach; ?>
                    <td class="text-left"><?= number_format((float) ($totals['ar_trade'] ?? 0), 2) ?></td>
                    <td class="text-left"><?= number_format((float) ($totals['ar_others'] ?? 0), 2) ?></td>
                    <td></td>
                    <td></td>
                    <td class="text-left"><?= number_format((float) ($totals['dr'] ?? 0), 2) ?></td>
                    <td class="text-left"><?= number_format((float) ($totals['cr'] ?? 0), 2) ?></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
<?php endif; ?>
<?= $this->endSection() ?>
