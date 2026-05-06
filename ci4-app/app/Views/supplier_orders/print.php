<!doctype html>
<?php
/**
 * @var array{id?: int|string, name?: string|null}|null $supplier
 * @var string $fromDate
 * @var string $toDate
 * @var string $poNo
 * @var string $statusFilter
 * @var list<array<string, int|float|string|null>> $orders
 * @var int|float|string $totalOrdered
 * @var int|float|string $totalPickedUp
 * @var int|float|string $totalBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Supplier PO Report</title>
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
    $printedDates = array_filter(array_column($orders ?? [], 'date'));
    $firstDate = $fromDate ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $toDate ?: (! empty($printedDates) ? max($printedDates) : '');
    ?>

    <div class="header">
        <?php if ($logoSrc !== ''): ?>
            <img class="print-logo" src="<?= esc($logoSrc) ?>" alt="SRC Enterprises logo">
        <?php endif; ?>

        <div class="company-title">SRC ENTERPRISES INC</div>
        <div class="report-title">Supplier PO Report</div>
        <?php if ($supplier): ?>
            <div class="meta">Supplier: <?= esc((string) ($supplier['name'] ?? '')) ?></div>
        <?php endif; ?>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
        <?php if (($poNo ?? '') !== ''): ?>
            <div class="meta">PO filter: <?= esc((string) $poNo) ?></div>
        <?php endif; ?>
        <div class="meta">Status: <?= esc(ucfirst((string) ($statusFilter ?? 'all'))) ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>PO #</th>
                <th class="text-right">Purchase Qty</th>
                <th class="text-right">Picked Qty</th>
                <th class="text-right">Balance Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td class="text-center" colspan="7">No supplier POs found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $index => $order): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($order['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($order['supplier_name'] ?? ($supplier['name'] ?? ''))) ?></td>
                        <td><?= esc((string) ($order['po_no'] ?? '')) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['qty_ordered_total'] ?? 0), 5)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['qty_picked_up_total'] ?? 0), 5)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($order['qty_balance_total'] ?? 0), 5)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($orders)): ?>
            <tfoot>
                <tr>
                    <th colspan="4">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalOrdered ?? 0), 5)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totalPickedUp ?? 0), 5)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totalBalance ?? 0), 5)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>