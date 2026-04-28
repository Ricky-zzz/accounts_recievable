<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OtherAccountSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'account_code' => '1000',
                'name' => 'Accounts Receivable Trade old',
                'type' => 'cr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '2000',
                'name' => 'Loans Payable',
                'type' => 'cr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '3000',
                'name' => 'Interest Income',
                'type' => 'cr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '3100',
                'name' => 'Miscellaneous Income',
                'type' => 'cr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '4000',
                'name' => 'Handling/Delivery Charges',
                'type' => 'dr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '4100',
                'name' => 'Salaries and Wages',
                'type' => 'cr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '4200',
                'name' => 'Taxes and Licenses',
                'type' => 'dr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '4300',
                'name' => 'Commission Expenses',
                'type' => 'dr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '4400',
                'name' => 'Sales Discount',
                'type' => 'dr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '4500',
                'name' => 'Household Expenses - CMR',
                'type' => 'dr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_code' => '5000',
                'name' => 'Retained Earnings',
                'type' => 'dr',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $accountCodes = array_column($data, 'account_code');
        $existing = array_column(
            $this->db->table('other_accounts')
                ->select('account_code')
                ->whereIn('account_code', $accountCodes)
                ->get()
                ->getResultArray(),
            'account_code'
        );

        $data = array_values(array_filter($data, static function (array $row) use ($existing) {
            return ! in_array($row['account_code'], $existing, true);
        }));

        if ($data === []) {
            return;
        }

        $this->db->table('other_accounts')->insertBatch($data);
    }
}
