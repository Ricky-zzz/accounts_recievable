<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Client Ledger Report</title>
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
        <div class="meta">Client Ledger Report</div>
        <div class="meta">Client: <?= esc($selectedClient['name'] ?? '') ?></div>
        <div class="meta">Last Balance: <?= esc(number_format((float) ($openingBalance ?? 0), 2)) ?></div>
        <div class="meta">Date Range: <?= esc($start ?: 'All') ?> to <?= esc($end ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>DR#</th>
                <th>PR#</th>
                <th>Account Title</th>
                <th>Qty</th>
                <th>Price</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Collection</th>
                <th class="text-right">Other Accounts</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="10">No deliveries in range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['entry_date']) ?></td>
                        <td><?= esc($row['dr_no'] ?? '') ?></td>
                        <td><?= esc($row['pr_no'] ?? '') ?></td>
                        <td><?= esc($row['account_title'] ?? '') ?></td>
                        <td><?= esc($row['qty'] ?? '') ?></td>
                        <td><?= esc($row['price'] ?? '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['collection'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['other_accounts'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($rows)): ?>
            <tfoot>
                <tr>
                    <th colspan="6">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) ($totals['amount'] ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totals['collection'] ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totals['other_accounts'] ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totals['ending_balance'] ?? 0), 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <?php if (empty($rows)): ?>
        <div class="footer-note">Generated with the selected date range.</div>
    <?php endif; ?>
</body>

</html>