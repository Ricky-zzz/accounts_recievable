<?php

namespace App\Models;

use CodeIgniter\Model;

class OtherAccountModel extends Model
{
    protected $table = 'other_accounts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['account_code', 'name', 'type'];
    protected $useTimestamps = true;
}
