<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'product_id'   => 'PROD-001',
                'product_name' => 'Premium Steel Widgets',
                'unit_price'   => 45.99,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_id'   => 'PROD-002',
                'product_name' => 'Aluminum Components',
                'unit_price'   => 32.50,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_id'   => 'PROD-003',
                'product_name' => 'Industrial Fasteners',
                'unit_price'   => 12.75,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_id'   => 'PROD-004',
                'product_name' => 'Precision Bearings',
                'unit_price'   => 89.99,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_id'   => 'PROD-005',
                'product_name' => 'Electronic Modules',
                'unit_price'   => 155.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_id'   => 'PROD-006',
                'product_name' => 'Rubber Seals',
                'unit_price'   => 8.99,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        $productIds = array_column($data, 'product_id');
        $existing = array_column(
            $this->db->table('products')
                ->select('product_id')
                ->whereIn('product_id', $productIds)
                ->get()
                ->getResultArray(),
            'product_id'
        );

        $data = array_values(array_filter($data, static function (array $row) use ($existing) {
            return ! in_array($row['product_id'], $existing, true);
        }));

        if ($data === []) {
            return;
        }

        $this->db->table('products')->insertBatch($data);
    }
}
