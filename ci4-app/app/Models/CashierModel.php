<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierModel extends Model
{
    protected $table = 'cashiers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'username', 'password_hash', 'is_active'];
    protected $useTimestamps = true;
}
