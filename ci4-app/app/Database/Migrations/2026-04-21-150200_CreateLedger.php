<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLedger extends Migration
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
            'entry_date' => [
                'type' => 'DATE',
            ],
            'dr_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pr_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'qty' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'collection' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'delivery_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'payment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['client_id', 'entry_date']);
        $this->forge->addKey('delivery_id');
        $this->forge->addKey('payment_id');
        $this->forge->addForeignKey('client_id', 'clients', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('delivery_id', 'deliveries', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('ledger');
    }

    public function down()
    {
        $this->forge->dropTable('ledger');
    }
}
