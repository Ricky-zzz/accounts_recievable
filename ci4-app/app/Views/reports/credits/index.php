<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Credits Report</h1>
            <p class="mt-1 text-sm muted">Client credit limits, current balances, and available balances.</p>
        </div>
        <a class="btn" href="<?= base_url('reports/credits/print?' . http_build_query(['sort' => $sort])) ?>" target="_blank">Print</a>
    </div>

    <form method="get" action="<?= base_url('reports/credits') ?>" class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium" for="sort">Available Balance</label>
            <select class="input mt-1" id="sort" name="sort">
                <option value="asc" <?= ($sort ?? 'asc') === 'asc' ? 'selected' : '' ?>>Ascending</option>
                <option value="desc" <?= ($sort ?? 'asc') === 'desc' ? 'selected' : '' ?>>Descending</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="btn btn-secondary" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?= base_url('reports/credits') ?>">Clear</a>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client Name</th>
                <th class="text-right">Credit Limit</th>
                <th class="text-right">Current Balance</th>
                <th class="text-right">Available Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5">No clients found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) (((int) ($currentPage ?? 1) - 1) * (int) ($perPage ?? 0) + $index + 1)) ?></td>
                        <td><?= esc($row['client_name']) ?></td>
                        <td class="text-left"><?= esc(number_format((float) $row['credit_limit'], 2)) ?></td>
                        <td class="text-left"><?= esc(number_format((float) $row['current_balance'], 2)) ?></td>
                        <td class="text-left"><?= esc(number_format((float) $row['available_balance'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total Balance</th>
                <th class="text-left"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
                <th></th>
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
                    <a class="btn btn-secondary" href="<?= base_url('reports/credits?' . http_build_query(['sort' => $sort, 'page' => $currentPage - 1])) ?>">Previous</a>
                <?php endif; ?>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a class="btn btn-secondary" href="<?= base_url('reports/credits?' . http_build_query(['sort' => $sort, 'page' => $currentPage + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
