<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeliveryHistories extends Migration
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
            'delivery_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'edited_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'edit',
            ],
            'old_delivery_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'old_items_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'new_delivery_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'new_items_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'change_summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['delivery_id', 'created_at']);
        $this->forge->addKey('edited_by');
        $this->forge->addKey('action');
        $this->forge->addForeignKey('delivery_id', 'deliveries', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('edited_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('delivery_histories');
    }

    public function down()
    {
        $this->forge->dropTable('delivery_histories');
    }
}
