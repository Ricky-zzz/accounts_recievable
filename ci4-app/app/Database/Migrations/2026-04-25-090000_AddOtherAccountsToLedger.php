<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOtherAccountsToLedger extends Migration
{
    public function up()
    {
        $db = db_connect();
        $existingFields = array_map(static fn ($field) => $field->name ?? '', $db->getFieldData('ledger'));
        $columns = [];

        if (! in_array('account_title', $existingFields, true)) {
            $columns['account_title'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'collection',
            ];
        }

        if (! in_array('other_accounts', $existingFields, true)) {
            $columns['other_accounts'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
                'after'      => 'account_title',
            ];
        }

        if (! empty($columns)) {
            $this->forge->addColumn('ledger', $columns);
        }
    }

    public function down()
    {
        $db = db_connect();
        $existingFields = array_map(static fn ($field) => $field->name ?? '', $db->getFieldData('ledger'));
        $fields = [];

        if (in_array('other_accounts', $existingFields, true)) {
            $fields[] = 'other_accounts';
        }

        if (in_array('account_title', $existingFields, true)) {
            $fields[] = 'account_title';
        }

        if (! empty($fields)) {
            $this->forge->dropColumn('ledger', $fields);
        }
    }
}