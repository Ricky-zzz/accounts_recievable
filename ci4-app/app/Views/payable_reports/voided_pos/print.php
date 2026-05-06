<!doctype html>
<?php
/**
 * @var string $fromVoidedDate
 * @var string $toVoidedDate
 * @var string $poNo
 * @var list<array{id?: int|string, date?: string|null, po_no?: string|null, supplier_name?: string|null, qty_ordered_total?: int|float|string|null, qty_picked_up_total?: int|float|string|null, qty_balance_total?: int|float|string|null, voided_at?: string|null, void_reason?: string|null}> $rows
 * @var int|float|string $totalOrdered
 * @var int|float|string $totalPickedUp
 * @var int|float|string $totalBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Voided POs Report</title>
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
    $firstDate = $fromVoidedDate;
    $lastDate = $toVoidedDate;

    if ($firstDate === '' || $lastDate === '') {
        foreach ($rows ?? [] as $row) {
            $voidedAt = (string) ($row['voided_at'] ?? '');
            if ($voidedAt === '') {
                continue;
            }

            $voidedDate = substr($voidedAt, 0, 10);
            if ($firstDate === '' || $voidedDate < $firstDate) {
                $firstDate = $voidedDate;
            }

            if ($lastDate === '' || $voidedDate > $lastDate) {
                $lastDate = $voidedDate;
            }
        }
    }
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
        <div class="report-title">Voided POs Report</div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
        <?php if (($poNo ?? '') !== ''): ?>
            <div class="meta">PO filter: <?= esc((string) $poNo) ?></div>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>PO #</th>
                <th>Date</th>
                <th>Voided At</th>
                <th>Supplier</th>
                <th class="text-right">Ordered Qty</th>
                <th class="text-right">Picked Qty</th>
                <th class="text-right">Balance Qty</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="9">No voided POs found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['voided_at'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['supplier_name'] ?? '')) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['qty_ordered_total'] ?? 0), 5)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['qty_picked_up_total'] ?? 0), 5)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['qty_balance_total'] ?? 0), 5)) ?></td>
                        <td><?= esc((string) ($row['void_reason'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5">Totals</th>
                <th class="text-right"><?= esc(number_format((float) ($totalOrdered ?? 0), 5)) ?></th>
                <th class="text-right"><?= esc(number_format((float) ($totalPickedUp ?? 0), 5)) ?></th>
                <th class="text-right"><?= esc(number_format((float) ($totalBalance ?? 0), 5)) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>