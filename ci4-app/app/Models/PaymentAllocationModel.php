<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentAllocationModel extends Model
{
    protected $table = 'payment_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['payment_id', 'delivery_id', 'amount', 'created_at'];
    protected $useTimestamps = false;
}
