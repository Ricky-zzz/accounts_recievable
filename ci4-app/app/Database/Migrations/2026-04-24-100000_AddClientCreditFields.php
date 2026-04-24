<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClientCreditFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('clients', [
            'credit_limit' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
                'after'      => 'phone',
            ],
            'payment_term' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'credit_limit',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('clients', ['credit_limit', 'payment_term']);
    }
}
