<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name'       => 'Acme Corporation',
                'address'    => '123 Business Ave, Suite 100, New York, NY 10001',
                'email'      => 'contact@acmecorp.com',
                'phone'      => '+1-212-555-0101',
                'credit_limit' => 1000000,
                'payment_term' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Global Industries Inc',
                'address'    => '456 Commerce Boulevard, Los Angeles, CA 90001',
                'email'      => 'info@globalindustries.com',
                'phone'      => '+1-213-555-0202',
                'credit_limit' => 750000,
                'payment_term' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Tech Solutions Ltd',
                'address'    => '789 Digital Drive, San Francisco, CA 94102',
                'email'      => 'sales@techsolutions.com',
                'phone'      => '+1-415-555-0303',
                'credit_limit' => 500000,
                'payment_term' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Manufacturing Pro Services',
                'address'    => '321 Industrial Park, Chicago, IL 60601',
                'email'      => 'procurement@mfgpro.com',
                'phone'      => '+1-312-555-0404',
                'credit_limit' => 850000,
                'payment_term' => 45,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Enterprise Solutions Group',
                'address'    => '654 Corporate Plaza, Houston, TX 77002',
                'email'      => 'accounts@enterprisesolutions.com',
                'phone'      => '+1-713-555-0505',
                'credit_limit' => 1200000,
                'payment_term' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Premier Distribution Network',
                'address'    => '987 Trade Center, Miami, FL 33101',
                'email'      => 'orders@premierdist.com',
                'phone'      => '+1-305-555-0606',
                'credit_limit' => 650000,
                'payment_term' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $emails = array_column($data, 'email');
        $existing = array_column(
            $this->db->table('clients')
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

        $this->db->table('clients')->insertBatch($data);
    }
}
