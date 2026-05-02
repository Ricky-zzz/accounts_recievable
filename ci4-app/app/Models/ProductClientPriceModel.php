<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductClientPriceModel extends Model
{
    protected $table = 'product_client_prices';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['product_id', 'client_id', 'price'];
    protected $useTimestamps = true;
}
