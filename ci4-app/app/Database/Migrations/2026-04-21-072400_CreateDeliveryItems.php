<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeliveryItems extends Migration
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
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'qty' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'line_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('delivery_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('delivery_id', 'deliveries', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('delivery_items');
    }

    public function down()
    {
        $this->forge->dropTable('delivery_items');
    }
}
