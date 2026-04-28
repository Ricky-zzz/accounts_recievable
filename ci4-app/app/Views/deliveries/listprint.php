<!doctype html>
<?php
/**
 * @var array{id: int|string, name: string} $client
 * @var string $fromDate
 * @var string $toDate
 * @var string $drNo
 * @var list<array{dr_no?: string|null, date?: string|null, due_date?: string|null, payment_term?: int|string|null, total_amount?: int|float|string|null, balance?: int|float|string|null}> $deliveries
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Client Deliveries Report</title>
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

        tfoot .text-right {
            text-align: left;
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
    $printedDates = array_filter(array_column($deliveries ?? [], 'date'));
    $firstDate = $fromDate ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $toDate ?: (! empty($printedDates) ? max($printedDates) : '');
    ?>
    <div class="header">
        <?php if ($logoSrc !== ''): ?>
            <img class="print-logo" src="<?= esc($logoSrc) ?>" alt="SRC Enterprises logo">
        <?php endif; ?>
        <div class="company-title">SRC ENTERPRISES INC</div>
        <div class="report-title">Client Deliveries Report</div>
        <div class="meta">Client: <?= esc((string) ($client['name'] ?? '')) ?></div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>DR #</th>
                <th>Due Date</th>
                <th>Term</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr>
                    <td class="text-center" colspan="7">No deliveries found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($deliveries as $index => $delivery): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $delivery['date']) ?></td>
                        <td><?= esc((string) ($delivery['dr_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($delivery['due_date'] ?? '')) ?></td>
                        <td><?= esc(($delivery['payment_term'] ?? '') !== '' ? $delivery['payment_term'] . ' days' : '') ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($delivery['total_amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($delivery['balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($deliveries)): ?>
            <tfoot>
                <tr>
                    <th colspan="5">Totals</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalAmount ?? 0), 2)) ?></th>
                    <th class="text-right"><?= esc(number_format((float) ($totalBalance ?? 0), 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>
