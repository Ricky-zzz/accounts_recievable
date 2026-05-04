<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierOrderModel extends Model
{
    protected $table = 'supplier_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'supplier_id',
        'po_no',
        'date',
        'status',
        'void_reason',
        'voided_at',
    ];
    protected $useTimestamps = true;
}
