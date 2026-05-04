<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierOrderItemModel extends Model
{
    protected $table = 'supplier_order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'supplier_order_id',
        'product_id',
        'qty_ordered',
        'qty_picked_up',
        'qty_balance',
    ];
    protected $useTimestamps = true;
}
