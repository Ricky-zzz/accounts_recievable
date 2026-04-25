<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CashierSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name'            => 'Maria Santos',
                'username'        => 'maria.santos',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name'            => 'Juan Rodriguez',
                'username'        => 'juan.rodriguez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name'            => 'Angela Martinez',
                'username'        => 'angela.martinez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name'            => 'Carlos Perez',
                'username'        => 'carlos.perez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name'            => 'Isabel Gonzalez',
                'username'        => 'isabel.gonzalez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name'            => 'Diego Lopez',
                'username'        => 'diego.lopez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 0,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}
