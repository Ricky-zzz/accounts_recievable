<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        $this->call(AdminUserSeeder::class);
        $this->call(CashierSeeder::class);
        $this->call(ClientSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(ProductSeeder::class);
    }
}
