<!doctype html>
<?php
/**
 * @var string $fromVoidedDate
 * @var string $toVoidedDate
 * @var string $drNo
 * @var list<array{date?: string|null, dr_no?: string|null, client_name?: string|null, due_date?: string|null, total_amount?: int|float|string|null, balance?: int|float|string|null, voided_at?: string|null, void_reason?: string|null}> $rows
 * @var int|float|string $totalAmount
 * @var int|float|string $totalBalance
 */
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Voided Deliveries Report</title>
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
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #333;
            padding: 6px 8px;
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
    </style>
</head>

<body>
    <?php
    $asOf = date('Y-m-d');
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
        <div class="report-title">Voided Deliveries Report</div>
        <div class="meta">Date from: <?= esc($firstDate ?: 'All') ?> to <?= esc($lastDate ?: 'All') ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>DR #</th>
                <th>Date</th>
                <th>Due Date</th>
                <th>Voided At</th>
                <th>Client</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Balance</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="text-center" colspan="9">No voided deliveries found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['dr_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['due_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['voided_at'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['client_name'] ?? '')) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                        <td class="text-right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                        <td><?= esc((string) ($row['void_reason'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-left"><strong>Totals</strong></td>
                <td class="text-right">
                    <strong><?= esc(number_format((float) $totalAmount, 2)) ?></strong>
                </td>
                <td class="text-right">
                    <strong><?= esc(number_format((float) $totalBalance, 2)) ?></strong>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>