<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayablesTables extends Migration
{
    public function up()
    {
        $this->createSuppliers();
        $this->createPurchaseOrders();
        $this->createPurchaseOrderItems();
        $this->createPurchaseOrderHistories();
        $this->createPayables();
        $this->createPayableAllocations();
        $this->createPayableLedger();
    }

    public function down()
    {
        $this->forge->dropTable('payable_ledger', true);
        $this->forge->dropTable('payable_allocations', true);
        $this->forge->dropTable('payables', true);
        $this->forge->dropTable('purchase_order_histories', true);
        $this->forge->dropTable('purchase_order_items', true);
        $this->forge->dropTable('purchase_orders', true);
        $this->forge->dropTable('suppliers', true);
    }

    private function createSuppliers(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'credit_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'payment_term' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('suppliers', true);
    }

    private function createPurchaseOrders(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'po_no' => ['type' => 'VARCHAR', 'constraint' => 50],
            'date' => ['type' => 'DATE'],
            'payment_term' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'due_date' => ['type' => 'DATE', 'null' => true],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'void_reason' => ['type' => 'TEXT', 'null' => true],
            'voided_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['supplier_id', 'date']);
        $this->forge->addKey('due_date');
        $this->forge->addUniqueKey(['supplier_id', 'po_no']);
        $this->forge->addForeignKey('supplier_id', 'suppliers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('purchase_orders', true);
    }

    private function createPurchaseOrderItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'qty' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'line_total' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('purchase_order_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('purchase_order_items', true);
    }

    private function createPurchaseOrderHistories(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'edited_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'edit'],
            'old_purchase_order_json' => ['type' => 'LONGTEXT', 'null' => true],
            'old_items_json' => ['type' => 'LONGTEXT', 'null' => true],
            'new_purchase_order_json' => ['type' => 'LONGTEXT', 'null' => true],
            'new_items_json' => ['type' => 'LONGTEXT', 'null' => true],
            'change_summary' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['purchase_order_id', 'created_at']);
        $this->forge->addKey('edited_by');
        $this->forge->addKey('action');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('edited_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('purchase_order_histories', true);
    }

    private function createPayables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'pr_no' => ['type' => 'INT', 'constraint' => 11],
            'date' => ['type' => 'DATE'],
            'method' => ['type' => 'VARCHAR', 'constraint' => 20],
            'amount_received' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'amount_allocated' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'excess_used' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'payer_bank' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'check_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deposit_bank_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'posted'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['supplier_id', 'date']);
        $this->forge->addUniqueKey(['user_id', 'pr_no']);
        $this->forge->addForeignKey('supplier_id', 'suppliers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('deposit_bank_id', 'banks', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('payables', true);
    }

    private function createPayableAllocations(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'payable_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('payable_id');
        $this->forge->addKey('purchase_order_id');
        $this->forge->addForeignKey('payable_id', 'payables', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('payable_allocations', true);
    }

    private function createPayableLedger(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'entry_date' => ['type' => 'DATE'],
            'po_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'pr_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'qty' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'payables' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'payment' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'account_title' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'other_accounts' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'balance' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'payable_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['supplier_id', 'entry_date']);
        $this->forge->addKey('purchase_order_id');
        $this->forge->addKey('payable_id');
        $this->forge->addForeignKey('supplier_id', 'suppliers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('payable_id', 'payables', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('payable_ledger', true);
    }
}
