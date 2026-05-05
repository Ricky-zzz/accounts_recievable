<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierOrderHistoryModel extends Model
{
    protected $table = 'supplier_order_histories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'supplier_order_id',
        'edited_by',
        'action',
        'old_supplier_order_json',
        'old_items_json',
        'new_supplier_order_json',
        'new_items_json',
        'change_summary',
        'created_at',
    ];
    protected $useTimestamps = false;
}
