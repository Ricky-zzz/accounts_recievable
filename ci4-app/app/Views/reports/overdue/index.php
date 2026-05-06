<?php
/**
 * @var string $asOf
 * @var string $fromDueDate
 * @var string $toDueDate
 * @var string $drNo
 * @var string $dueSort
 * @var list<array{client_name?: string|null, dr_no?: string|null, date?: string|null, due_date?: string|null, amount?: int|float|string|null, balance?: int|float|string|null}> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 */
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="space-y-6">
    <?php
    $filterQuery = [
        'dr_no' => $drNo ?? '',
        'from_due_date' => $fromDueDate ?? '',
        'to_due_date' => $toDueDate ?? '',
        'due_sort' => $dueSort ?? 'asc',
    ];
    ?>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Overdue Report</h1>
            <p class="mt-1 text-sm muted">Open delivery receipts past due as of <?= esc($asOf) ?>.</p>
        </div>
        <a class="btn" href="<?= base_url('reports/overdue/print?' . http_build_query($filterQuery)) ?>" target="_blank">Print</a>
    </div>

    <form method="get" action="<?= base_url('reports/overdue') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
        <input type="hidden" name="from_due_date" x-ref="fromDate" value="<?= esc($fromDueDate ?? '') ?>">
        <input type="hidden" name="to_due_date" x-ref="toDate" value="<?= esc($toDueDate ?? '') ?>">
        <div class="grid gap-4 md:grid-cols-5">
        <div>
            <label class="block text-sm font-medium" for="dr_no">DR Number</label>
            <input class="input mt-1" id="dr_no" name="dr_no" value="<?= esc($drNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="from_due_date">From Due Date</label>
            <input class="input mt-1" id="from_due_date" x-ref="fromDateDraft" type="date" value="<?= esc($fromDueDate ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_due_date">To Due Date</label>
            <input class="input mt-1" id="to_due_date" x-ref="toDateDraft" type="date" value="<?= esc($toDueDate ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium" for="due_sort">Due Order</label>
            <select class="input mt-1" id="due_sort" name="due_sort" @change="$el.form.requestSubmit()">
                <option value="asc" <?= ($dueSort ?? 'asc') === 'asc' ? 'selected' : '' ?>>Oldest Due First</option>
                <option value="desc" <?= ($dueSort ?? 'asc') === 'desc' ? 'selected' : '' ?>>Latest Due First</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="btn btn-strong" type="submit" @click="$refs.fromDate.value = $refs.fromDateDraft.value; $refs.toDate.value = $refs.toDateDraft.value">Filter</button>
            <a class="btn btn-secondary" href="<?= base_url('reports/overdue') ?>">Clear</a>
        </div>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
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
                    <td colspan="7">No overdue accounts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                        <td><?= esc((string) ($row['client_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['dr_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['due_date'] ?? '')) ?></td>
                        <td class="text-left"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="text-left"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-highlight">
                <th colspan="5">Totals</th>
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
                    <a class="btn btn-secondary" href="<?= base_url('reports/overdue?' . http_build_query($filterQuery + ['page' => $currentPage - 1])) ?>">Previous</a>
                <?php endif; ?>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a class="btn btn-secondary" href="<?= base_url('reports/overdue?' . http_build_query($filterQuery + ['page' => $currentPage + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
