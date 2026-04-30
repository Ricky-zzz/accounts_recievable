<!doctype html>
<?php
/**
 * @var array{id?: int|string, name?: string|null, address?: string|null} $supplier
 * @var string $asOfDate
 * @var string $dueDate
 * @var list<array{entry_date?: string|null, po_no?: string|null, due_date?: string|null, amount?: int|float|string|null, payment?: int|float|string|null, balance?: int|float|string|null}> $rows
 * @var int|float|string $totalPayables
 * @var int|float|string $totalPayments
 * @var int|float|string $endingBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Payables Statement</title>

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
            margin-bottom: 14px;
        }

        .print-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .company-title {
            font-size: 24px;
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

        .supplier-info {
            margin: 12px 0 14px;
            font-size: 13px;
            text-align: left;
            line-height: 1.45;
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
        <div class="report-title">ACCOUNT'S PAYABLE</div>
        <div class="meta">As of: <?= esc($asOfDate ?? date('Y-m-d')) ?></div>
    </div>

    <div class="supplier-info">
        <div><strong>Supplier:</strong> <?= esc((string) ($supplier['name'] ?? '')) ?></div>

        <?php if (! empty($supplier['address'])): ?>
            <div><strong>Address:</strong> <?= esc((string) $supplier['address']) ?></div>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 110px;">Date</th>
                <th>Description</th>
                <th class="text-right" style="width: 140px;">Purchase Order</th>
                <th class="text-right" style="width: 140px;">Payments</th>
                <th class="text-right" style="width: 120px;">Balance</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="6">
                        No active purchase orders with remaining balance.
                    </td>
                </tr>
            <?php else: ?>

                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['entry_date'] ?? '')) ?></td>
                        <td>
                            <?php
                            $rawPoNo = trim((string) ($row['po_no'] ?? ''));
                            if ($rawPoNo !== '') {
                                $description = stripos($rawPoNo, 'PO') === 0 ? $rawPoNo : 'PO ' . $rawPoNo;
                            } else {
                                $description = 'Purchase Order';
                            }
                            ?>

                            <?= esc($description) ?>
                        </td>

                        <td class="text-right">
                            <?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?>
                        </td>

                        <td class="text-right">
                            <?= esc(number_format((float) ($row['payment'] ?? 0), 2)) ?>
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
                        <?= esc(number_format((float) $totalPayables, 2)) ?>
                    </th>
                    <th class="text-right">
                        <?= esc(number_format((float) $totalPayments, 2)) ?>
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
                <strong>Total Amount to Settle:</strong>
            </td>
            <td class="amount-due">
                <?= esc(number_format((float) $endingBalance, 2)) ?>
            </td>
        </tr>
    </table>

    <?php if (! empty($dueDate)): ?>
        <div class="payment-notice">
            Settlement is due on or before <?= esc($dueDate) ?>.
            Failure to settle the outstanding balance may result in delayed processing or additional charges.
        </div>
    <?php else: ?>
        <div class="payment-notice">
            Settlement is due immediately upon receipt of this statement.
            Failure to settle the outstanding balance may result in delayed processing or additional charges.
        </div>
    <?php endif; ?>

</body>
</html>
