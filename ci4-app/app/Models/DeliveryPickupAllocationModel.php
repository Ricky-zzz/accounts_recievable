<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryPickupAllocationModel extends Model
{
    protected $table = 'delivery_pickup_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'delivery_id',
        'purchase_order_id',
        'product_id',
        'qty_allocated',
        'created_at',
    ];
    protected $useTimestamps = false;
}
