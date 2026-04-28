<!doctype html>
<?php
/**
 * @var list<array{client_name: string, credit_limit: int|float|string, current_balance: int|float|string, available_balance: int|float|string}> $rows
 * @var string $sort
 * @var int|float|string $totalBalance
 */
?>
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
    <div class="header">
        <?php
        $logoPath = FCPATH . 'logo.png';
        $logoSrc = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
        ?>
        <?php if ($logoSrc !== ''): ?>
            <img class="print-logo" src="<?= esc($logoSrc) ?>" alt="SRC Enterprises logo">
        <?php endif; ?>
        <div class="company-title">SRC ENTERPRISES INC</div>
        <div class="report-title">Credits Report</div>
        <div class="meta">Available Balance Sort: <?= esc(strtoupper($sort)) ?></div>
    </div>

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
                    <td class="text-center" colspan="5">No clients found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $row['client_name']) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $row['credit_limit'], 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $row['current_balance'], 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) $row['available_balance'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total Balance</th>
                <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
