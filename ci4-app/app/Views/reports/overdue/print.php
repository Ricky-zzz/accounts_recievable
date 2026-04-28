<!doctype html>
<?php
/**
 * @var string $asOf
 * @var string $fromDueDate
 * @var string $toDueDate
 * @var string $drNo
 * @var string $dueSort
 * @var list<array{client_name?: string|null, dr_no?: string|null, date?: string|null, due_date?: string|null, amount?: int|float|string|null, balance?: int|float|string|null}> $rows
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Overdue Report</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #111;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
        }

        .print-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .company-title {
            font-size: 32px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .report-title {
            margin-top: 14px;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta {
            margin-top: 6px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #333;
            padding: 6px 8px;
        }

        .table th {
            background: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        tfoot .text-right {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <?php
    $printedDates = array_filter(array_column($rows ?? [], 'due_date'));
    $firstDate = $fromDueDate ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $toDueDate ?: (! empty($printedDates) ? max($printedDates) : '');
    ?>
    <div class="header">
        <?php
        $logoPath = FCPATH . 'logo.png';
        $logoSrc = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
        ?>
        <?php if ($logoSrc !== ''): ?>
            <img class="print-logo" src="<?= esc($logoSrc) ?>" alt="SRC Enterprises logo">
        <?php endif; ?>
        <div class="company-title">SRC ENTERPRISES INC</div>
        <div class="report-title">Overdue Report</div>
        <div class="meta">As of: <?= esc($asOf) ?></div>
        <div class="meta">DR Number: <?= esc(($drNo ?? '') !== '' ? $drNo : 'All') ?></div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
        <div class="meta">Due Order: <?= ($dueSort ?? 'asc') === 'desc' ? 'Latest Due First' : 'Oldest Due First' ?></div>
    </div>

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
                    <td class="text-center" colspan="7">No overdue accounts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['client_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['dr_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['due_date'] ?? '')) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5">Totals</th>
                <th class="text-right"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
