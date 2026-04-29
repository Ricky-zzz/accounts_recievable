<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name' => 'Northstar Paper Supply',
                'address' => '112 Warehouse Road, Quezon City',
                'email' => 'orders@northstarpaper.test',
                'phone' => '+63-2-8555-1101',
                'credit_limit' => 500000,
                'payment_term' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Metro Packaging Traders',
                'address' => '45 Trade Avenue, Pasig City',
                'email' => 'billing@metropackaging.test',
                'phone' => '+63-2-8555-2202',
                'credit_limit' => 350000,
                'payment_term' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Prime Office Goods',
                'address' => '78 Supply Street, Makati City',
                'email' => 'accounts@primeoffice.test',
                'phone' => '+63-2-8555-3303',
                'credit_limit' => 250000,
                'payment_term' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Harbor Industrial Materials',
                'address' => '9 Portside Lane, Manila',
                'email' => 'ap@harborindustrial.test',
                'phone' => '+63-2-8555-4404',
                'credit_limit' => 750000,
                'payment_term' => 45,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $emails = array_column($data, 'email');
        $existing = array_column(
            $this->db->table('suppliers')
                ->select('email')
                ->whereIn('email', $emails)
                ->get()
                ->getResultArray(),
            'email'
        );

        $data = array_values(array_filter($data, static function (array $row) use ($existing) {
            return ! in_array($row['email'], $existing, true);
        }));

        if ($data === []) {
            return;
        }

        $this->db->table('suppliers')->insertBatch($data);
    }
}
