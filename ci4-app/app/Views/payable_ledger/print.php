<!doctype html>
<?php
/**
 * @var array{id: int|string, name: string}|null $selectedSupplier
 * @var string $start
 * @var string $end
 * @var int|float|string $openingBalance
 * @var list<array<string, int|float|string|null>> $rows
 * @var array<string, int|float> $totals
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Supplier Ledger Report</title>
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

        .table th.text-right,
        .table td.text-right {
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
    <?php
    $printedDates = array_filter(array_column($rows ?? [], 'entry_date'));
    $firstDate = $start ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $end ?: (! empty($printedDates) ? max($printedDates) : '');
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
        <div class="report-title">Supplier Ledger Report</div>
        <div class="meta">Supplier: <?= esc((string) ($selectedSupplier['name'] ?? '')) ?></div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>RR#</th>
                <th>CV#</th>
                <th>Account</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Payables</th>
                <th class="text-right">Payment</th>
                <th class="text-right">Other Accounts</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Balance Forwarded</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"><?= esc(number_format((float) ($openingBalance ?? 0), 2)) ?></td>
            </tr>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="11">No ledger rows in range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['entry_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['pr_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['account_title'] ?? '')) ?></td>
                        <td class="text-right"><?= ($row['qty'] ?? '') !== '' ? esc(number_format((float) ($row['qty'] ?? 0), 2)) : '' ?></td>
                        <td class="text-right"><?= ($row['price'] ?? '') !== '' ? esc(number_format((float) ($row['price'] ?? 0), 2)) : '' ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['payables'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['payment'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['other_accounts'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($rows)): ?>
            <tfoot>
                <tr>
                    <th colspan="7">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) ($totals['payables'] ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totals['payment'] ?? 0), 2)) ?></th>
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
