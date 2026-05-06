<!doctype html>
<?php
/**
 * @var array<string, int|float|string|null> $supplierOrder
 * @var string $fromDate
 * @var string $toDate
 * @var int|float|string $orderedTotal
 * @var int|float|string $endingBalance
 * @var list<array<string, int|float|string|null>> $rows
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Purchase Order Ledger</title>
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
    </style>
</head>

<body>
    <?php
    $logoPath = FCPATH . 'logo.png';
    $logoSrc = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    $ledgerDates = array_values(array_filter(
        array_map(static fn(array $row): string => (string) ($row['date'] ?? ''), $rows ?? []),
        static fn(string $date): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
    ));
    $firstDate = ($fromDate ?? '') !== '' ? $fromDate : (! empty($ledgerDates) ? min($ledgerDates) : (string) ($supplierOrder['date'] ?? ''));
    $lastDate = ($toDate ?? '') !== '' ? $toDate : (! empty($ledgerDates) ? max($ledgerDates) : (string) ($supplierOrder['date'] ?? ''));
    ?>
    <div class="header">
        <?php if ($logoSrc !== ''): ?>
            <img class="print-logo" src="<?= esc($logoSrc) ?>" alt="SRC Enterprises logo">
        <?php endif; ?>
        <div class="company-title">SRC ENTERPRISES INC</div>
        <div class="report-title">Purchase Order Ledger: <?= esc((string) ($supplierOrder['po_no'] ?? '')) ?></div>
        <div class="meta">Supplier: <?= esc((string) ($supplierOrder['supplier_name'] ?? '')) ?></div>
        <div class="meta">Purchase Qty: <?= esc(number_format((float) ($orderedTotal ?? 0), 5)) ?></div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>PO</th>
                <th>RR</th>
                <th class="text-right">Qty</th>
                <th class="text-right">PO Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['rr_no'] ?? '')) ?></td>
                    <td class="text-right"><?= ($row['qty'] ?? null) === null ? '' : esc(number_format((float) ($row['qty'] ?? 0), 5)) ?></td>
                    <td class="text-right"><?= esc(number_format((float) ($row['po_balance'] ?? 0), 5)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Ending PO Balance</th>
                <th class="text-right"><?= esc(number_format((float) ($endingBalance ?? 0), 5)) ?></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>