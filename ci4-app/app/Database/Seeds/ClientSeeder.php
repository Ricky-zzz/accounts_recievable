<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name'       => 'Acme Corporation',
                'address'    => '123 Business Ave, Suite 100, New York, NY 10001',
                'email'      => 'contact@acmecorp.com',
                'phone'      => '+1-212-555-0101',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Global Industries Inc',
                'address'    => '456 Commerce Boulevard, Los Angeles, CA 90001',
                'email'      => 'info@globalindustries.com',
                'phone'      => '+1-213-555-0202',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Tech Solutions Ltd',
                'address'    => '789 Digital Drive, San Francisco, CA 94102',
                'email'      => 'sales@techsolutions.com',
                'phone'      => '+1-415-555-0303',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Manufacturing Pro Services',
                'address'    => '321 Industrial Park, Chicago, IL 60601',
                'email'      => 'procurement@mfgpro.com',
                'phone'      => '+1-312-555-0404',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Enterprise Solutions Group',
                'address'    => '654 Corporate Plaza, Houston, TX 77002',
                'email'      => 'accounts@enterprisesolutions.com',
                'phone'      => '+1-713-555-0505',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Premier Distribution Network',
                'address'    => '987 Trade Center, Miami, FL 33101',
                'email'      => 'orders@premierdist.com',
                'phone'      => '+1-305-555-0606',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('clients')->insertBatch($data);
    }
}
