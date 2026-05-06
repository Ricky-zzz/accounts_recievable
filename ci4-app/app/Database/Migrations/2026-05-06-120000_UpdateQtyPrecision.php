<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateQtyPrecision extends Migration
{
    public function up()
    {
        $this->updateQtyScale(5);
    }

    public function down()
    {
        $this->updateQtyScale(2);
    }

    private function updateQtyScale(int $scale): void
    {
        $this->modifyQtyColumn('delivery_items', 'qty', $scale);
        $this->modifyQtyColumn('purchase_order_items', 'qty', $scale);
        $this->modifyQtyColumn('purchase_order_items', 'po_qty_balance_after', $scale, true);
        $this->modifyQtyColumn('supplier_order_items', 'qty_ordered', $scale);
        $this->modifyQtyColumn('supplier_order_items', 'qty_picked_up', $scale, false, 0);
        $this->modifyQtyColumn('supplier_order_items', 'qty_balance', $scale);
        $this->modifyQtyColumn('delivery_pickup_allocations', 'qty_allocated', $scale);
        $this->modifyQtyColumn('ledger', 'qty', $scale, true);
        $this->modifyQtyColumn('payable_ledger', 'qty', $scale, true);
        $this->modifyQtyColumn('payable_ledger', 'po_balance', $scale, true);
    }

    private function modifyQtyColumn(
        string $table,
        string $column,
        int $scale,
        bool $nullable = false,
        $default = null
    ): void {
        if (! $this->tableExists($table) || ! $this->fieldExists($table, $column)) {
            return;
        }

        $definition = [
            'type' => 'DECIMAL',
            'constraint' => '12,' . $scale,
        ];

        if ($nullable) {
            $definition['null'] = true;
        }

        if ($default !== null) {
            $definition['default'] = $default;
        }

        $this->forge->modifyColumn($table, [
            $column => $definition,
        ]);
    }

    private function tableExists(string $table): bool
    {
        $row = $this->db
            ->query(
                'SELECT COUNT(*) AS found FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            )
            ->getRowArray();

        return (int) ($row['found'] ?? 0) > 0;
    }

    private function fieldExists(string $table, string $field): bool
    {
        $row = $this->db
            ->query(
                'SELECT COUNT(*) AS found FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $field]
            )
            ->getRowArray();

        return (int) ($row['found'] ?? 0) > 0;
    }
}
