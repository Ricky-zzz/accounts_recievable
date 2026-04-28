<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CashierSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name'            => 'Maria Santos',
                'username'        => 'maria.santos',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Juan Rodriguez',
                'username'        => 'juan.rodriguez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Angela Martinez',
                'username'        => 'angela.martinez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Carlos Perez',
                'username'        => 'carlos.perez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Isabel Gonzalez',
                'username'        => 'isabel.gonzalez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Diego Lopez',
                'username'        => 'diego.lopez',
                'password_hash'   => password_hash('password123', PASSWORD_BCRYPT),
                'type'            => 'cashier',
                'is_active'       => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];

        $usernames = array_column($data, 'username');
        $existing = array_column(
            $this->db->table('users')
                ->select('username')
                ->whereIn('username', $usernames)
                ->get()
                ->getResultArray(),
            'username'
        );

        $data = array_values(array_filter($data, static function (array $row) use ($existing) {
            return ! in_array($row['username'], $existing, true);
        }));

        if ($data === []) {
            return;
        }

        $this->db->table('users')->insertBatch($data);
    }
}
