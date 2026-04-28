<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $exists = $this->db->table('users')
            ->where('username', 'admin')
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'name' => 'System Admin',
            'username' => 'admin',
            'password_hash' => password_hash('admin1234', PASSWORD_DEFAULT),
            'type' => 'admin',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
