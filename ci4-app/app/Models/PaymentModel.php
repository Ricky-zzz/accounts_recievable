<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'client_id',
        'user_id',
        'pr_no',
        'date',
        'method',
        'amount_received',
        'amount_allocated',
        'excess_used',
        'payer_bank',
        'check_no',
        'deposit_bank_id',
        'status',
    ];
    protected $useTimestamps = true;
}
