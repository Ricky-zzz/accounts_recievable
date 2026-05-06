<!doctype html>
<?php
/**
 * @var array{id?: int|string, name?: string|null}|null $supplier
 * @var string $fromDate
 * @var string $toDate
 * @var string $poNo
 * @var list<array<string, int|float|string|null>> $purchaseOrders
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>RR / Pickups Report</title>
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
    </style>
</head>

<body>
    <?php
    $logoPath = FCPATH . 'logo.png';
    $logoSrc = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    $printedDates = array_filter(array_column($purchaseOrders ?? [], 'date'));
    $firstDate = $fromDate ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $toDate ?: (! empty($printedDates) ? max($printedDates) : '');
    $totalPaid = 0.0;
    foreach ($purchaseOrders ?? [] as $order) {
        if (isset($order['allocated_amount'])) {
            $totalPaid += (float) ($order['allocated_amount'] ?? 0);
        } else {
            $totalPaid += (float) ($order['total_amount'] ?? 0) - (float) ($order['balance'] ?? 0);
        }
    }
    ?>

    <div class="header">
        <?php if ($logoSrc !== ''): ?>
            <img class="print-logo" src="<?= esc($logoSrc) ?>" alt="SRC Enterprises logo">
        <?php endif; ?>

        <div class="company-title">SRC ENTERPRISES INC</div>
        <div class="report-title">RR / Pickups Report</div>
        <?php if ($supplier): ?>
            <div class="meta">Supplier: <?= esc((string) ($supplier['name'] ?? '')) ?></div>
        <?php endif; ?>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
        <?php if (($poNo ?? '') !== ''): ?>
            <div class="meta">RR filter: <?= esc((string) $poNo) ?></div>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>RR #</th>
                <th>Due Date</th>
                <th>Term</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchaseOrders)): ?>
                <tr>
                    <td class="text-center" colspan="9">No pickups found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchaseOrders as $index => $order): ?>
                    <?php
                    $paidAmount = isset($order['allocated_amount'])
                        ? (float) ($order['allocated_amount'] ?? 0)
                        : (float) ($order['total_amount'] ?? 0) - (float) ($order['balance'] ?? 0);
                    ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($order['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($order['supplier_name'] ?? ($supplier['name'] ?? ''))) ?></td>
                        <td><?= esc((string) ($order['po_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($order['due_date'] ?? '')) ?></td>
                        <td><?= esc(($order['payment_term'] ?? '') !== '' ? $order['payment_term'] . ' days' : '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['total_amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format($paidAmount, 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($purchaseOrders)): ?>
            <tfoot>
                <tr>
                    <th colspan="6">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                    <th class="text-right"><?= esc(number_format($totalPaid, 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>