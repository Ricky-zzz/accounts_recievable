<?php
/**
 * @var array{id: int|string, name: string}|null $selectedSupplier
 * @var string $start
 * @var string $end
 * @var int|float|string $openingBalance
 * @var list<array<string, int|float|string|null>> $rows
 * @var array<string, int|float> $totals
 */
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Ledger Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Supplier Ledger Report</h1>
    <div class="meta">Supplier: <?= esc((string) ($selectedSupplier['name'] ?? '')) ?> | Date: <?= esc($start) ?> to <?= esc($end) ?> | Opening: <?= esc(number_format((float) $openingBalance, 2)) ?></div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>PO#</th>
                <th>PR#</th>
                <th>Account</th>
                <th class="right">Payables</th>
                <th class="right">Payment</th>
                <th class="right">Other</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['entry_date'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['pr_no'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['account_title'] ?? '')) ?></td>
                    <td class="right"><?= esc(number_format((float) ($row['payables'] ?? 0), 2)) ?></td>
                    <td class="right"><?= esc(number_format((float) ($row['payment'] ?? 0), 2)) ?></td>
                    <td class="right"><?= esc(number_format((float) ($row['other_accounts'] ?? 0), 2)) ?></td>
                    <td class="right"><?= esc(number_format((float) ($row['balance'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="right">Totals</th>
                <th class="right"><?= esc(number_format((float) ($totals['payables'] ?? 0), 2)) ?></th>
                <th class="right"><?= esc(number_format((float) ($totals['payment'] ?? 0), 2)) ?></th>
                <th class="right"><?= esc(number_format((float) ($totals['other_accounts'] ?? 0), 2)) ?></th>
                <th class="right"><?= esc(number_format((float) ($totals['ending_balance'] ?? 0), 2)) ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
