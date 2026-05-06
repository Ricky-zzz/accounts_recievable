<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientModel extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'address', 'email', 'phone', 'credit_limit', 'payment_term', 'forwarded_balance'];
    protected $useTimestamps = true;
}
