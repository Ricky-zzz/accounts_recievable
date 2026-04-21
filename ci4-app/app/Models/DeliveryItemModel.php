<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryItemModel extends Model
{
    protected $table = 'delivery_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'delivery_id',
        'product_id',
        'qty',
        'unit_price',
        'line_total',
    ];
}
