<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderHistoryModel extends Model
{
    protected $table = 'purchase_order_histories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'purchase_order_id',
        'edited_by',
        'action',
        'old_purchase_order_json',
        'old_items_json',
        'new_purchase_order_json',
        'new_items_json',
        'change_summary',
        'created_at',
    ];
    protected $useTimestamps = false;
}
