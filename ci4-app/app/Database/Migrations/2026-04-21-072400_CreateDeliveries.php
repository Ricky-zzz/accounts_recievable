<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeliveries extends Migration
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
            'dr_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'open',
            ],
            'void_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'voided_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey(['client_id', 'dr_no']);
        $this->forge->addForeignKey('client_id', 'clients', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('deliveries');
    }

    public function down()
    {
        $this->forge->dropTable('deliveries');
    }
}
