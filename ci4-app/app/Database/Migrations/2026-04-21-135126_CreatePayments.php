<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayments extends Migration
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
            'client_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cashier_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pr_no' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'amount_received' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'amount_allocated' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'excess_used' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'payer_bank' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'check_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'deposit_bank_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'posted',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['client_id', 'date']);
        $this->forge->addUniqueKey(['cashier_id', 'pr_no']);
        $this->forge->addForeignKey('client_id', 'clients', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('cashier_id', 'cashiers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('deposit_bank_id', 'banks', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('payments');
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}
