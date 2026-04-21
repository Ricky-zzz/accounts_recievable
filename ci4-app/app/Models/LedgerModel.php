<?php

namespace App\Models;

use CodeIgniter\Model;

class LedgerModel extends Model
{
    protected $table = 'ledger';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'client_id',
        'entry_date',
        'dr_no',
        'pr_no',
        'qty',
        'price',
        'amount',
        'collection',
        'balance',
        'delivery_id',
        'payment_id',
        'created_at',
    ];
    protected $useTimestamps = false;
}
