<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSupplierOrderHistories extends Migration
{
    public function up()
    {
        if ($this->tableExists('supplier_order_histories')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'edited_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'edit'],
            'old_supplier_order_json' => ['type' => 'LONGTEXT', 'null' => true],
            'old_items_json' => ['type' => 'LONGTEXT', 'null' => true],
            'new_supplier_order_json' => ['type' => 'LONGTEXT', 'null' => true],
            'new_items_json' => ['type' => 'LONGTEXT', 'null' => true],
            'change_summary' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['supplier_order_id', 'created_at']);
        $this->forge->addKey('edited_by');
        $this->forge->addKey('action');
        $this->forge->addForeignKey('supplier_order_id', 'supplier_orders', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('edited_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('supplier_order_histories', true);
    }

    public function down()
    {
        $this->forge->dropTable('supplier_order_histories', true);
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
}
