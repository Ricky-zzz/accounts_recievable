<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderModel extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'supplier_id',
        'po_no',
        'date',
        'payment_term',
        'due_date',
        'total_amount',
        'status',
        'void_reason',
        'voided_at',
    ];
    protected $useTimestamps = true;
}
