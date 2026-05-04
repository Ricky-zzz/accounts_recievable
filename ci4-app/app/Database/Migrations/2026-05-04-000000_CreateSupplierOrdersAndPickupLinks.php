<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSupplierOrdersAndPickupLinks extends Migration
{
    public function up()
    {
        $this->createSupplierOrders();
        $this->createSupplierOrderItems();

        $this->addColumnIfMissing('purchase_orders', 'supplier_order_id', [
            'supplier_order_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'supplier_id',
            ],
        ]);

        $this->addColumnIfMissing('purchase_order_items', 'supplier_order_item_id', [
            'supplier_order_item_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'purchase_order_id',
            ],
        ]);

        $this->addColumnIfMissing('purchase_order_items', 'po_qty_balance_after', [
            'po_qty_balance_after' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'after' => 'line_total',
            ],
        ]);

        $this->addColumnIfMissing('payable_ledger', 'supplier_order_id', [
            'supplier_order_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'balance',
            ],
        ]);

        $this->addColumnIfMissing('payable_ledger', 'supplier_order_item_id', [
            'supplier_order_item_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'supplier_order_id',
            ],
        ]);

        $this->addColumnIfMissing('payable_ledger', 'po_balance', [
            'po_balance' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
                'after' => 'supplier_order_item_id',
            ],
        ]);
    }

    public function down()
    {
        $this->dropColumnIfExists('payable_ledger', 'po_balance');
        $this->dropColumnIfExists('payable_ledger', 'supplier_order_item_id');
        $this->dropColumnIfExists('payable_ledger', 'supplier_order_id');
        $this->dropColumnIfExists('purchase_order_items', 'po_qty_balance_after');
        $this->dropColumnIfExists('purchase_order_items', 'supplier_order_item_id');
        $this->dropColumnIfExists('purchase_orders', 'supplier_order_id');

        $this->forge->dropTable('supplier_order_items', true);
        $this->forge->dropTable('supplier_orders', true);
    }

    private function createSupplierOrders(): void
    {
        if ($this->tableExists('supplier_orders')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'po_no' => ['type' => 'VARCHAR', 'constraint' => 50],
            'date' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'void_reason' => ['type' => 'TEXT', 'null' => true],
            'voided_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['supplier_id', 'date']);
        $this->forge->addUniqueKey(['supplier_id', 'po_no']);
        $this->forge->addForeignKey('supplier_id', 'suppliers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('supplier_orders', true);
    }

    private function createSupplierOrderItems(): void
    {
        if ($this->tableExists('supplier_order_items')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'qty_ordered' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'qty_picked_up' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'qty_balance' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('supplier_order_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('supplier_order_id', 'supplier_orders', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('supplier_order_items', true);
    }

    private function addColumnIfMissing(string $table, string $column, array $definition): void
    {
        if (! $this->tableExists($table) || $this->fieldExists($table, $column)) {
            return;
        }

        $this->forge->addColumn($table, $definition);
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (! $this->tableExists($table) || ! $this->fieldExists($table, $column)) {
            return;
        }

        $this->forge->dropColumn($table, $column);
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
