<?php

namespace App\Models;

use CodeIgniter\Model;

class BankModel extends Model
{
    protected $table = 'banks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['bank_name', 'account_name', 'bank_number'];
    protected $useTimestamps = true;
}
