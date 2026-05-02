<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductClientPrices extends Migration
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
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'client_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
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
        $this->forge->addKey('product_id');
        $this->forge->addKey('client_id');
        $this->forge->addUniqueKey(['product_id', 'client_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('client_id', 'clients', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_client_prices');
    }

    public function down()
    {
        $this->forge->dropTable('product_client_prices');
    }
}
