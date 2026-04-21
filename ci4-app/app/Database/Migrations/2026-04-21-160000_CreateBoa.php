<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBoa extends Migration
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
            'date' => [
                'type' => 'DATE',
            ],
            'payor' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'reference' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'payment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ar_trade' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
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
        $this->forge->addKey(['payor', 'date']);
        $this->forge->addKey('reference');
        $this->forge->addKey('payment_id');
        $this->forge->addForeignKey('payor', 'clients', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('boa');
    }

    public function down()
    {
        $this->forge->dropTable('boa');
    }
}
