<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Payments Report</title>
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
    </style>
</head>

<body>
    <div class="header">
        <div class="title">SRC ENTERPRISES INC</div>
        <div class="meta">Payments Report</div>
        <div class="meta">Date Range: <?= esc($fromDate ?: 'All') ?> to <?= esc($toDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>PR #</th>
                <th class="text-right">Collections</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td class="text-center" colspan="4">No payments found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= esc($payment['date']) ?></td>
                        <td><?= esc($payment['client_name'] ?? '') ?></td>
                        <td><?= esc($payment['pr_no'] ?? '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($payment['amount_received'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($payments)): ?>
            <tfoot>
                <tr>
                    <th colspan="3">Total Collections</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalCollections ?? 0), 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>
