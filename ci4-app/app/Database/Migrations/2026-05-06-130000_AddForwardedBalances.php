<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddForwardedBalances extends Migration
{
    public function up()
    {
        $db = db_connect();

        $clientFields = array_map(static fn($field) => $field->name ?? '', $db->getFieldData('clients'));
        if (! in_array('forwarded_balance', $clientFields, true)) {
            $this->forge->addColumn('clients', [
                'forwarded_balance' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                    'after' => 'payment_term',
                ],
            ]);
        }

        $supplierFields = array_map(static fn($field) => $field->name ?? '', $db->getFieldData('suppliers'));
        if (! in_array('forwarded_balance', $supplierFields, true)) {
            $this->forge->addColumn('suppliers', [
                'forwarded_balance' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                    'after' => 'payment_term',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = db_connect();

        $clientFields = array_map(static fn($field) => $field->name ?? '', $db->getFieldData('clients'));
        if (in_array('forwarded_balance', $clientFields, true)) {
            $this->forge->dropColumn('clients', 'forwarded_balance');
        }

        $supplierFields = array_map(static fn($field) => $field->name ?? '', $db->getFieldData('suppliers'));
        if (in_array('forwarded_balance', $supplierFields, true)) {
            $this->forge->dropColumn('suppliers', 'forwarded_balance');
        }
    }
}
