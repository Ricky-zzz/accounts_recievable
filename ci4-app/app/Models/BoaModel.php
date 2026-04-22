<?php

namespace App\Models;

use CodeIgniter\Model;

class BoaModel extends Model
{
    protected $table = 'boa';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'date',
        'payor',
        'reference',
        'payment_id',
        'ar_trade',
        'ar_others',
        'account_title',
        'dr',
        'cr',
        'note',
        'description',
    ];
    protected $useTimestamps = true;
}
