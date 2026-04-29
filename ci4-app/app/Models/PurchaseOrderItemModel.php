<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderItemModel extends Model
{
    protected $table = 'purchase_order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'purchase_order_id',
        'product_id',
        'qty',
        'unit_price',
        'line_total',
    ];
}
