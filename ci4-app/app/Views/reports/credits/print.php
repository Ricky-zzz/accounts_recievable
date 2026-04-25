<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Credits Report</title>
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

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">SRC ENTERPRISES INC</div>
        <div class="meta">Credits Report</div>
        <div class="meta">Available Balance Sort: <?= esc(strtoupper($sort)) ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Client Name</th>
                <th class="text-right">Credit Limit</th>
                <th class="text-right">Current Balance</th>
                <th class="text-right">Available Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="4">No clients found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['client_name']) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $row['credit_limit'], 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $row['current_balance'], 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $row['available_balance'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total Balance</th>
                <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
