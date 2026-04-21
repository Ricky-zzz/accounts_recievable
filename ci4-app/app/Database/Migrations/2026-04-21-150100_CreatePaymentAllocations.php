<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentAllocations extends Migration
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
            'payment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'delivery_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_id');
        $this->forge->addKey('delivery_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('delivery_id', 'deliveries', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('payment_allocations');
    }

    public function down()
    {
        $this->forge->dropTable('payment_allocations');
    }
}
