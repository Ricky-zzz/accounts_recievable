<?php
/**
 * @var string $asOf
 * @var string $fromDueDate
 * @var string $toDueDate
 * @var string $poNo
 * @var string $dueSort
 * @var list<array{supplier_name?: string|null, po_no?: string|null, date?: string|null, due_date?: string|null, amount?: int|float|string|null, balance?: int|float|string|null}> $rows
 * @var int $allRowsCount
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 */
?>
<?= $this->extend('payables_layout') ?>
<?= $this->section('content') ?>
<?php
$filterQuery = [
    'po_no' => $poNo ?? '',
    'from_due_date' => $fromDueDate ?? '',
    'to_due_date' => $toDueDate ?? '',
    'due_sort' => $dueSort ?? 'asc',
];
?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Overdue Purchase Orders</h1>
            <p class="mt-1 text-sm muted">Open purchase orders past due as of <?= esc($asOf) ?>.</p>
        </div>
        <a class="btn" href="<?= base_url('payable-reports/overdue/print?' . http_build_query($filterQuery)) ?>" target="_blank">Print</a>
    </div>

    <form method="get" action="<?= base_url('payable-reports/overdue') ?>" class="filter-card rounded border border-gray-200 p-4" x-data>
        <div class="grid gap-4 md:grid-cols-5">
        <div>
            <label class="block text-sm font-medium" for="po_no">PO Number</label>
            <input class="input mt-1" id="po_no" name="po_no" value="<?= esc($poNo ?? '') ?>" @input.debounce.1000ms="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="from_due_date">From Due Date</label>
            <input class="input mt-1" id="from_due_date" name="from_due_date" type="date" value="<?= esc($fromDueDate ?? '') ?>" @change="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="to_due_date">To Due Date</label>
            <input class="input mt-1" id="to_due_date" name="to_due_date" type="date" value="<?= esc($toDueDate ?? '') ?>" @change="$el.form.requestSubmit()">
        </div>
        <div>
            <label class="block text-sm font-medium" for="due_sort">Due Order</label>
            <select class="input mt-1" id="due_sort" name="due_sort" @change="$el.form.requestSubmit()">
                <option value="asc" <?= ($dueSort ?? 'asc') === 'asc' ? 'selected' : '' ?>>Oldest Due First</option>
                <option value="desc" <?= ($dueSort ?? 'asc') === 'desc' ? 'selected' : '' ?>>Latest Due First</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <a class="btn btn-secondary" href="<?= base_url('payable-reports/overdue') ?>">Clear</a>
        </div>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Supplier Name</th>
                <th>PO #</th>
                <th>Date</th>
                <th>Due Date</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7">No overdue purchase orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                        <td><?= esc((string) ($row['supplier_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
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
            <div>Showing page <?= esc((string) $currentPage) ?> of <?= esc((string) $totalPages) ?>, total rows <?= esc((string) $allRowsCount) ?></div>
            <div class="flex items-center gap-2">
                <?php if (($currentPage ?? 1) > 1): ?>
                    <a class="btn btn-secondary" href="<?= base_url('payable-reports/overdue?' . http_build_query($filterQuery + ['page' => $currentPage - 1])) ?>">Previous</a>
                <?php endif; ?>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a class="btn btn-secondary" href="<?= base_url('payable-reports/overdue?' . http_build_query($filterQuery + ['page' => $currentPage + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
