<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeliveryTermsFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('deliveries', [
            'payment_term' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'date',
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'payment_term',
            ],
        ]);

        $db = db_connect();
        $db->query('CREATE INDEX idx_deliveries_due_date ON deliveries (due_date)');
    }

    public function down()
    {
        $db = db_connect();
        try {
            $db->query('DROP INDEX idx_deliveries_due_date ON deliveries');
        } catch (\Throwable $e) {
            // Ignore missing index during rollback.
        }

        $this->forge->dropColumn('deliveries', ['payment_term', 'due_date']);
    }
}
