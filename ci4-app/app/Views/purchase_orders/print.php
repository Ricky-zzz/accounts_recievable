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
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Orders Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin-bottom: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Purchase Orders Report</h1>
    <div class="meta">
        <?= $supplier ? 'Supplier: ' . esc((string) ($supplier['name'] ?? '')) . ' | ' : '' ?>
        Date: <?= esc((string) $fromDate) ?> to <?= esc((string) $toDate) ?>
        <?= $poNo !== '' ? ' | PO#: ' . esc((string) $poNo) : '' ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Supplier</th>
                <th>PO #</th>
                <th>Due Date</th>
                <th class="right">Total</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchaseOrders as $order): ?>
                <tr>
                    <td><?= esc((string) ($order['date'] ?? '')) ?></td>
                    <td><?= esc((string) ($order['supplier_name'] ?? ($supplier['name'] ?? ''))) ?></td>
                    <td><?= esc((string) ($order['po_no'] ?? '')) ?></td>
                    <td><?= esc((string) ($order['due_date'] ?? '')) ?></td>
                    <td class="right"><?= esc(number_format((float) ($order['total_amount'] ?? 0), 2)) ?></td>
                    <td class="right"><?= esc(number_format((float) ($order['balance'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="right">Totals</th>
                <th class="right"><?= esc(number_format((float) $totalAmount, 2)) ?></th>
                <th class="right"><?= esc(number_format((float) $totalBalance, 2)) ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
