<!doctype html>
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
            font-size: 12px;
            color: #111;
        }

        .header {
            margin-bottom: 16px;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta {
            margin-top: 6px;
            font-size: 12px;
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
    <div class="header">
        <div class="title">SRC ENTERPRISES INC</div>
        <div class="meta">BOA Report</div>
        <div class="meta">Date Range: <?= esc($from) ?> to <?= esc($to) ?></div>
    </div>

    <?php if ($tableMissing): ?>
        <div class="footer-note">BOA table is missing. Run migrations first.</div>
    <?php else: ?>
        <table class="table">
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
                        <td class="text-center" colspan="<?= 10 + count($bankColumns) ?>">No BOA records in this range.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= esc($row['date']) ?></td>
                            <td><?= esc($row['payor_name'] ?? $row['payor']) ?></td>
                            <td><?= esc($row['reference']) ?></td>
                            <?php foreach ($bankColumns as $column): ?>
                                <td class="text-right"><?= number_format((float) ($row[$column] ?? 0), 2) ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><?= number_format((float) ($row['ar_trade'] ?? 0), 2) ?></td>
                            <td class="text-right"><?= number_format((float) ($row['ar_others'] ?? 0), 2) ?></td>
                            <td><?= esc($row['account_title'] ?? '') ?></td>
                            <td class="text-right"><?= number_format((float) ($row['dr'] ?? 0), 2) ?></td>
                            <td class="text-right"><?= number_format((float) ($row['cr'] ?? 0), 2) ?></td>
                            <td><?= esc($row['note'] ?? '') ?></td>
                            <td><?= esc($row['description'] ?? '') ?></td>
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
                        <?php foreach ($bankColumns as $column): ?>
                            <th class="text-right"><?= number_format((float) ($totals['bankColumns'][$column] ?? 0), 2) ?></th>
                        <?php endforeach; ?>
                        <th class="text-right"><?= number_format((float) ($totals['ar_trade'] ?? 0), 2) ?></th>
                        <th class="text-right"><?= number_format((float) ($totals['ar_others'] ?? 0), 2) ?></th>
                        <th></th>
                        <th class="text-right"><?= number_format((float) ($totals['dr'] ?? 0), 2) ?></th>
                        <th class="text-right"><?= number_format((float) ($totals['cr'] ?? 0), 2) ?></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    <?php endif; ?>
</body>

</html>