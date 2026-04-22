<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateBoaForOtherAccounts extends Migration
{
    public function up()
    {
        $db = db_connect();

        if (! $db->tableExists('boa')) {
            return;
        }

        if ($db->fieldExists('reference', 'boa')) {
            $this->forge->modifyColumn('boa', [
                'reference' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
            ]);
        }

        if (! $db->fieldExists('note', 'boa')) {
            $this->forge->addColumn('boa', [
                'note' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
            ]);
        }

        if (! $db->fieldExists('description', 'boa')) {
            $this->forge->addColumn('boa', [
                'description' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $db = db_connect();

        if (! $db->tableExists('boa')) {
            return;
        }

        if ($db->fieldExists('note', 'boa')) {
            $this->forge->dropColumn('boa', 'note');
        }

        if ($db->fieldExists('description', 'boa')) {
            $this->forge->dropColumn('boa', 'description');
        }

        if ($db->fieldExists('reference', 'boa')) {
            $this->forge->modifyColumn('boa', [
                'reference' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
            ]);
        }
    }
}
