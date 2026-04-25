<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Statement of Account</title>
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

        .summary {
            margin-top: 14px;
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            padding: 4px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">SRC ENTERPRISES INC</div>
        <div class="meta">Statement of Account</div>
        <div class="meta">For: <?= esc($client['name'] ?? '') ?></div>
        <?php if (! empty($client['address'])): ?>
            <div class="meta">Address: <?= esc($client['address']) ?></div>
        <?php endif; ?>
        <div class="meta">As of: <?= esc($asOf) ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
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
                    <td class="text-center" colspan="5">No overdue balances found for this client.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['dr_no'] ?? '') ?></td>
                        <td><?= esc($row['date'] ?? '') ?></td>
                        <td><?= esc($row['due_date'] ?? '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($rows)): ?>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th class="text-right"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <table class="summary">
        <tr>
            <td><strong>Total Amount Due:</strong></td>
            <td class="text-right"><strong><?= esc(number_format((float) $totalBalance, 2)) ?></strong></td>
        </tr>
    </table>
</body>

</html>
