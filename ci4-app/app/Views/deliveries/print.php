<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Deliveries Report</title>
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
        <div class="meta">Deliveries Report</div>
        <div class="meta">Date Range: <?= esc($fromDate ?: 'All') ?> to <?= esc($toDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Due Date</th>
                <th>Term</th>
                <th>DR #</th>
                <th>Client</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr>
                    <td class="text-center" colspan="7">No deliveries found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($deliveries as $delivery): ?>
                    <tr>
                        <td><?= esc($delivery['date']) ?></td>
                        <td><?= esc($delivery['due_date'] ?? '') ?></td>
                        <td><?= esc(($delivery['payment_term'] ?? '') !== '' ? $delivery['payment_term'] . ' days' : '') ?></td>
                        <td><?= esc($delivery['dr_no'] ?? '') ?></td>
                        <td><?= esc($delivery['client_name'] ?? '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($delivery['total_amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($delivery['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($deliveries)): ?>
            <tfoot>
                <tr>
                    <th colspan="5">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalAmount ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totalBalance ?? 0), 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>
