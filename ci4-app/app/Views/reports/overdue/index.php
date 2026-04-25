<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Overdue Report</h1>
            <p class="mt-1 text-sm muted">Open delivery receipts past due as of <?= esc($asOf) ?>.</p>
        </div>
        <a class="btn" href="<?= base_url('reports/overdue/print?' . http_build_query(['from_due_date' => $fromDueDate, 'to_due_date' => $toDueDate])) ?>" target="_blank">Print</a>
    </div>

    <form method="get" action="<?= base_url('reports/overdue') ?>" class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium" for="from_due_date">From Due Date</label>
            <input class="input mt-1" id="from_due_date" name="from_due_date" type="date" value="<?= esc($fromDueDate ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_due_date">To Due Date</label>
            <input class="input mt-1" id="to_due_date" name="to_due_date" type="date" value="<?= esc($toDueDate ?? '') ?>">
        </div>
        <div class="flex items-end gap-2">
            <button class="btn btn-secondary" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?= base_url('reports/overdue') ?>">Clear</a>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>DR #</th>
                <th>Date</th>
                <th>Due Date</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6">No overdue accounts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['client_name'] ?? '') ?></td>
                        <td><?= esc($row['dr_no'] ?? '') ?></td>
                        <td><?= esc($row['date'] ?? '') ?></td>
                        <td><?= esc($row['due_date'] ?? '') ?></td>
                        <td class="text-left"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="text-left"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Totals</th>
                <th class="text-left"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                <th class="text-left"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
            </tr>
        </tfoot>
    </table>

    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="flex items-center justify-between gap-4 text-sm muted">
            <div>
                Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if (($currentPage ?? 1) > 1): ?>
                    <a class="btn btn-secondary" href="<?= base_url('reports/overdue?' . http_build_query(['from_due_date' => $fromDueDate, 'to_due_date' => $toDueDate, 'page' => $currentPage - 1])) ?>">Previous</a>
                <?php endif; ?>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a class="btn btn-secondary" href="<?= base_url('reports/overdue?' . http_build_query(['from_due_date' => $fromDueDate, 'to_due_date' => $toDueDate, 'page' => $currentPage + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
