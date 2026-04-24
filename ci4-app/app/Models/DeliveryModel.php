<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryModel extends Model
{
    protected $table = 'deliveries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'client_id',
        'dr_no',
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
