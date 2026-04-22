<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateOtherAccountsType extends Migration
{
    public function up()
    {
        $db = db_connect();

        if (! $db->tableExists('other_accounts')) {
            return;
        }

        $db->table('other_accounts')->set('type', 'dr')->where('type', 'debit')->update();
        $db->table('other_accounts')->set('type', 'cr')->where('type', 'credit')->update();

        $this->forge->modifyColumn('other_accounts', [
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['dr', 'cr'],
                'default'    => 'dr',
            ],
        ]);
    }

    public function down()
    {
        $db = db_connect();

        if (! $db->tableExists('other_accounts')) {
            return;
        }

        $db->table('other_accounts')->set('type', 'debit')->where('type', 'dr')->update();
        $db->table('other_accounts')->set('type', 'credit')->where('type', 'cr')->update();

        $this->forge->modifyColumn('other_accounts', [
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['debit', 'credit'],
                'default'    => 'debit',
            ],
        ]);
    }
}
