<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Permissions extends BaseConfig
{
    /**
     * Lightweight role-to-permission map for the current custom auth setup.
     * Keep permissions scoped by module/action so this can grow without
     * duplicating controllers or views.
     *
     * @var array<string, list<string>>
     */
    public array $matrix = [
        'admin' => ['*'],
        'cashier' => [
            'clients.view',
            'products.view',
            'deliveries.view',
            'payments.view',
            'payables.view',
            'suppliers.view',
            'purchase_orders.view',
            'payable_ledger.view',
            'reports.credits.view',
            'reports.overdue.view',
            'payable_reports.credits.view',
            'payable_reports.overdue.view',
            'payable_reports.voided.view',
            'boa.view',
        ],
    ];
}
