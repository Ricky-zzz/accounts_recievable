<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBanks extends Migration
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
            'bank_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'account_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'bank_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
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
        $this->forge->createTable('banks');
    }

    public function down()
    {
        $this->forge->dropTable('banks');
    }
}
