<!doctype html>
<?php
/**
 * @var array{id?: int|string, name?: string|null, address?: string|null} $client
 * @var string $start
 * @var string $end
 * @var string $dueDate
 * @var int|float|string $openingBalance
 * @var list<array{entry_date?: string|null, dr_no?: string|null, pr_no?: string|null, account_title?: string|null, amount?: int|float|string|null, collection?: int|float|string|null, other_accounts?: int|float|string|null, balance?: int|float|string|null}> $rows
 * @var int|float|string $totalDebit
 * @var int|float|string $totalCredit
 * @var int|float|string $endingBalance
 */
?>
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

        .text-center {
            text-align: center;
        }

        tfoot th {
            background: #eaeaea;
            font-weight: 700;
        }

            .table th.text-right,
            .table td.text-right {
                text-align: right;
            }

        .summary {
            margin-top: 16px;
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            padding: 5px 0;
            font-size: 15px;
        }

        .summary .amount-due {
            text-align: right;
            font-weight: 700;
            font-size: 16px;
        }

        .payment-notice {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px solid #333;
            background: #f7f7f7;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
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
        <div class="report-title">Statement of Account</div>

        <div class="meta">For: <?= esc((string) ($client['name'] ?? '')) ?></div>

        <?php if (! empty($client['address'])): ?>
            <div class="meta">Address: <?= esc((string) $client['address']) ?></div>
        <?php endif; ?>

        <div class="meta">
            Billing period: <?= esc($start ?: 'All') ?> to <?= esc($end ?: 'All') ?>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 100px;">Date</th>
                <th>Description</th>
                <th class="text-right" style="width: 150px;">Deliveries (Debit)</th>
                <th class="text-right" style="width: 165px;">Collections (Credit)</th>
                <th class="text-right" style="width: 120px;">Balance</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="6">
                        No transactions found for this billing period.
                    </td>
                </tr>
            <?php else: ?>

                <tr>
                    <td>1</td>
                    <td><?= esc($start ?: '') ?></td>
                    <td>Previous Balance</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">
                        <?= esc(number_format((float) ($openingBalance ?? 0), 2)) ?>
                    </td>
                </tr>

                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 2)) ?></td>
                        <td><?= esc((string) ($row['entry_date'] ?? '')) ?></td>

                        <td>
                            <?php
                            $description = '';

                            if ((float) ($row['amount'] ?? 0) > 0 && ! empty($row['dr_no'])) {
                                $description = 'DR ' . $row['dr_no'];
                            } elseif ((float) ($row['collection'] ?? 0) > 0 && ! empty($row['pr_no'])) {
                                $description = 'PR ' . $row['pr_no'];
                            } elseif ((float) ($row['other_accounts'] ?? 0) > 0 && ! empty($row['account_title'])) {
                                $description = (string) $row['account_title'];
                            } elseif (! empty($row['account_title'])) {
                                $description = (string) $row['account_title'];
                            }
                            ?>

                            <?= esc($description) ?>
                        </td>

                        <td class="text-right">
                            <?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?>
                        </td>

                        <td class="text-right">
                            <?= esc(number_format((float) (($row['collection'] ?? 0) + ($row['other_accounts'] ?? 0)), 2)) ?>
                        </td>

                        <td class="text-right">
                            <?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php endif; ?>
        </tbody>

        <?php if (! empty($rows)): ?>
            <tfoot>
                <tr>
                    <th colspan="3">Totals</th>
                    <th class="text-right">
                        <?= esc(number_format((float) $totalDebit, 2)) ?>
                    </th>
                    <th class="text-right">
                        <?= esc(number_format((float) $totalCredit, 2)) ?>
                    </th>
                    <th class="text-right">
                        <?= esc(number_format((float) $endingBalance, 2)) ?>
                    </th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <table class="summary">
        <tr>
            <td>
                <strong>Total Amount Due:</strong>
            </td>
            <td class="amount-due">
                <?= esc(number_format((float) $endingBalance, 2)) ?>
            </td>
        </tr>
    </table>

    <?php if (! empty($dueDate)): ?>
        <div class="payment-notice">
            Payment is due on or before <?= esc($dueDate) ?>.
            Failure to settle the outstanding balance may result in service interruption,
            delayed processing, or additional charges.
        </div>
    <?php else: ?>
        <div class="payment-notice">
            Payment is due immediately upon receipt of this statement.
            Failure to settle the outstanding balance may result in service interruption,
            delayed processing, or additional charges.
        </div>
    <?php endif; ?>

</body>
</html>