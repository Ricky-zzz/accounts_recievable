<?php

namespace App\Models;

use CodeIgniter\Model;

class PayableLedgerModel extends Model
{
    protected $table = 'payable_ledger';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'supplier_id',
        'entry_date',
        'po_no',
        'pr_no',
        'qty',
        'price',
        'payables',
        'payment',
        'account_title',
        'other_accounts',
        'balance',
        'purchase_order_id',
        'payable_id',
        'created_at',
    ];
    protected $useTimestamps = false;
}
