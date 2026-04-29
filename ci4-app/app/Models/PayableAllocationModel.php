<?php

namespace App\Models;

use CodeIgniter\Model;

class PayableAllocationModel extends Model
{
    protected $table = 'payable_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['payable_id', 'purchase_order_id', 'amount', 'created_at'];
    protected $useTimestamps = false;
}
