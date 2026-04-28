<!doctype html>
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
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>BOA Report</title>
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
            font-size: 13px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #333;
            padding: 6px 8px;
            vertical-align: top;
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

        .footer-note {
            margin-top: 8px;
            font-size: 11px;
            color: #555;
        }
    </style>
</head>

<body>
    <?php
    $printedDates = array_filter(array_column($records ?? [], 'date'));
    $firstDate = $from ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $to ?: (! empty($printedDates) ? max($printedDates) : '');
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
        <div class="report-title">BOA Report</div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
    </div>

    <?php if ($tableMissing): ?>
        <div class="footer-note">BOA table is missing. Run migrations first.</div>
    <?php else: ?>
        <table class="table">
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
                        <td class="text-center" colspan="<?= 10 + count($bankColumns) ?>">No BOA records in this range.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $index => $row): ?>
                        <tr>
                            <td><?= esc((string) ($index + 1)) ?></td>
                            <td><?= esc((string) $row['date']) ?></td>
                            <td><?= esc((string) ($row['payor_name'] ?? $row['payor'])) ?></td>
                            <td><?= esc((string) $row['reference']) ?></td>
                            <?php foreach ($bankColumns as $column): ?>
                                <td class="text-right"><?= number_format((float) ($row[$column] ?? 0), 2) ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><?= number_format((float) ($row['ar_trade'] ?? 0), 2) ?></td>
                            <td class="text-right"><?= number_format((float) ($row['ar_others'] ?? 0), 2) ?></td>
                            <td><?= esc((string) ($row['description'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['account_title'] ?? '')) ?></td>
                            <td class="text-right"><?= number_format((float) ($row['dr'] ?? 0), 2) ?></td>
                            <td class="text-right"><?= number_format((float) ($row['cr'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (! empty($records)): ?>
                <tfoot>
                    <tr>
                        <th>Totals:</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <?php foreach ($bankColumns as $column): ?>
                            <th class="text-right"><?= number_format((float) ($totals['bankColumns'][$column] ?? 0), 2) ?></th>
                        <?php endforeach; ?>
                        <th class="text-right"><?= number_format((float) ($totals['ar_trade'] ?? 0), 2) ?></th>
                        <th class="text-right"><?= number_format((float) ($totals['ar_others'] ?? 0), 2) ?></th>
                        <th></th>
                        <th></th>
                        <th class="text-right"><?= number_format((float) ($totals['dr'] ?? 0), 2) ?></th>
                        <th class="text-right"><?= number_format((float) ($totals['cr'] ?? 0), 2) ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    <?php endif; ?>
</body>

</html>
