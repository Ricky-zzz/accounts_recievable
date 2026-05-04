<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierReceiptRangeModel extends Model
{
    protected $table = 'cashier_receipt_ranges';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'start_no', 'end_no', 'next_no', 'status'];
    protected $useTimestamps = false;

    public function findNextAvailableNumber(int $userId, int $startNo, int $endNo): ?int
    {
        if ($userId <= 0 || $startNo <= 0 || $endNo <= 0 || $startNo > $endNo) {
            return null;
        }

        $rows = db_connect()->query(
            'SELECT pr_no FROM payments WHERE user_id = ? AND pr_no BETWEEN ? AND ?
             UNION
             SELECT pr_no FROM payables WHERE user_id = ? AND pr_no BETWEEN ? AND ?',
            [$userId, $startNo, $endNo, $userId, $startNo, $endNo]
        )->getResultArray();

        $used = [];
        foreach ($rows as $row) {
            $prNo = (int) ($row['pr_no'] ?? 0);
            if ($prNo > 0) {
                $used[$prNo] = true;
            }
        }

        for ($candidate = $startNo; $candidate <= $endNo; $candidate++) {
            if (! isset($used[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }
}
