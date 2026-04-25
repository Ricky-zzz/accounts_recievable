<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCashierReceiptRanges extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'start_no' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'end_no' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'next_no' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('cashier_receipt_ranges');
    }

    public function down()
    {
        $this->forge->dropTable('cashier_receipt_ranges');
    }
}
