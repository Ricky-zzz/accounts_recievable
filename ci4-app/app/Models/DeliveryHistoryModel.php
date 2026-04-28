<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryHistoryModel extends Model
{
    protected $table = 'delivery_histories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'delivery_id',
        'edited_by',
        'action',
        'old_delivery_json',
        'old_items_json',
        'new_delivery_json',
        'new_items_json',
        'change_summary',
        'created_at',
    ];
    protected $useTimestamps = false;
}
