<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierModel extends Model
{
    protected $table = 'suppliers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'address', 'email', 'phone', 'credit_limit', 'payment_term', 'forwarded_balance'];
    protected $useTimestamps = true;
}
