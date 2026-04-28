<!doctype html>
<?php
/**
 * @var array{id: int|string, name: string} $client
 * @var string $fromDate
 * @var string $toDate
 * @var list<array{pr_no?: int|string|null, date?: string|null, amount_received?: int|float|string|null}> $payments
 * @var int|float|string $totalCollections
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Client Payments Report</title>
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
    $printedDates = array_filter(array_column($payments ?? [], 'date'));
    $firstDate = $fromDate ?: (! empty($printedDates) ? min($printedDates) : '');
    $lastDate = $toDate ?: (! empty($printedDates) ? max($printedDates) : '');
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
        <div class="report-title">Client Payments Report</div>
        <div class="meta">Client: <?= esc((string) ($client['name'] ?? '')) ?></div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>PR #</th>
                <th class="text-right">Collections</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td class="text-center" colspan="4">No payments found for the selected date range.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $index => $payment): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) $payment['date']) ?></td>
                        <td><?= esc((string) ($payment['pr_no'] ?? '')) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($payment['amount_received'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($payments)): ?>
            <tfoot>
                <tr>
                    <th colspan="3">Total Collections</th>
                    <th class="text-right"><?= esc(number_format((float) ($totalCollections ?? 0), 2)) ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</body>

</html>
