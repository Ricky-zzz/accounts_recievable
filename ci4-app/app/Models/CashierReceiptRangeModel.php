<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierReceiptRangeModel extends Model
{
    protected $table = 'cashier_receipt_ranges';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['cashier_id', 'start_no', 'end_no', 'next_no', 'status'];
    protected $useTimestamps = false;
}
