<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['product_id', 'product_name', 'unit_price'];
    protected $useTimestamps = true;
}
