<!doctype html>
<?php
/**
 * @var array{id?: int|string, name?: string|null}|null $supplier
 * @var string $fromDate
 * @var string $toDate
 * @var string $prNo
 * @var list<array{supplier_name?: string|null, pr_no?: int|string|null, date?: string|null, amount_received?: int|float|string|null, amount_allocated?: int|float|string|null}> $payables
 * @var int|float|string $totalPayments
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= $supplier ? 'Supplier Payables Report' : 'Payables Report' ?></title>
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
    $printedDates = array_filter(array_column($payables ?? [], 'date'));
    $firstDate = $fromDate ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $toDate ?: (! empty($printedDates) ? max($printedDates) : '');
    $showSupplierColumn = empty($supplier);
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
        <div class="report-title"><?= $supplier ? 'Supplier Payables Report' : 'Payables Report' ?></div>
        <?php if ($supplier): ?>
            <div class="meta">Supplier: <?= esc((string) ($supplier['name'] ?? '')) ?></div>
        <?php endif; ?>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
        <?php if (($prNo ?? '') !== ''): ?>
            <div class="meta">PR filter: <?= esc((string) $prNo) ?></div>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <?php if ($showSupplierColumn): ?>
                    <th>Supplier</th>
                <?php endif; ?>
                <th>CV #</th>
                <th class="text-right">Amount Paid</th>
                <th class="text-right">Allocated</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payables)): ?>
                <tr>
                    <td class="text-center" colspan="<?= $showSupplierColumn ? '6' : '5' ?>">No payables found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payables as $index => $payable): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($payable['date'] ?? '')) ?></td>
                        <?php if ($showSupplierColumn): ?>
                            <td><?= esc((string) ($payable['supplier_name'] ?? '')) ?></td>
                        <?php endif; ?>
                        <td><?= esc((string) ($payable['pr_no'] ?? '')) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($payable['amount_received'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($payable['amount_allocated'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($payables)): ?>
            <tfoot>
                <tr>
                    <th colspan="<?= $showSupplierColumn ? '4' : '3' ?>">Total Payments</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalPayments ?? 0), 2)) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>
