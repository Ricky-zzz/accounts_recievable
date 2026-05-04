<?php

namespace App\Services;

use App\Models\BankModel;
use App\Models\CashierReceiptRangeModel;
use App\Models\PayableAllocationModel;
use App\Models\PayableLedgerModel;
use App\Models\PayableModel;
use RuntimeException;

class PayablePostingService
{
    public function getActiveReceiptRange(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $rangeModel = new CashierReceiptRangeModel();
        $range = $rangeModel
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (! $range) {
            return null;
        }

        $rangeStart = max((int) $range['next_no'], (int) $range['start_no']);
        $rangeEnd = (int) $range['end_no'];

        if ($rangeStart > $rangeEnd) {
            $rangeModel->update($range['id'], ['status' => 'closed', 'next_no' => $rangeStart]);
            return null;
        }

        $nextAvailable = $rangeModel->findNextAvailableNumber($userId, $rangeStart, $rangeEnd);
        if ($nextAvailable === null) {
            $rangeModel->update($range['id'], ['status' => 'closed', 'next_no' => $rangeEnd + 1]);
            return null;
        }

        if ($nextAvailable !== (int) $range['next_no']) {
            $rangeModel->update($range['id'], ['next_no' => $nextAvailable]);
            $range['next_no'] = $nextAvailable;
        }

        return $range;
    }

    public function post(array $data): array
    {
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $date = (string) ($data['date'] ?? '');
        $method = (string) ($data['method'] ?? '');
        $amountPaid = (float) ($data['amount_received'] ?? 0);
        $depositBankId = (int) ($data['deposit_bank_id'] ?? 0);
        $payerBank = trim((string) ($data['payer_bank'] ?? ''));
        $checkNo = trim((string) ($data['check_no'] ?? ''));
        $allocations = $data['allocations'] ?? [];
        $fixedAccountRows = $this->normalizeFixedAccounts($data['fixed_accounts'] ?? []);
        $apOtherDescription = trim((string) ($data['ar_other_description'] ?? ''));
        $apOtherAmount = max(0.0, (float) ($data['ar_other_amount'] ?? 0));

        if ($supplierId <= 0 || $userId <= 0 || $date === '' || $amountPaid <= 0) {
            throw new RuntimeException('Please complete the payable form.');
        }

        if (! in_array($method, ['cash', 'bank', 'check'], true)) {
            throw new RuntimeException('Select a payment method.');
        }

        if ($depositBankId <= 0) {
            throw new RuntimeException('Select the bank for this payable.');
        }

        if ($method === 'check' && ($checkNo === '' || $payerBank === '')) {
            throw new RuntimeException('Check number and supplier bank are required.');
        }

        if ($method === 'bank' && $payerBank === '') {
            throw new RuntimeException('Supplier bank is required.');
        }

        if (! (new BankModel())->find($depositBankId)) {
            throw new RuntimeException('Selected bank is invalid.');
        }

        $fixedAccountsTotal = 0.0;
        foreach ($fixedAccountRows as $row) {
            $fixedAccountsTotal += (float) $row['amount'];
        }

        $cleanAllocations = $this->cleanAllocations($supplierId, is_array($allocations) ? $allocations : []);
        $allocatedTotal = 0.0;
        foreach ($cleanAllocations as $allocation) {
            $allocatedTotal += (float) $allocation['amount'];
        }

        if (empty($cleanAllocations) && $fixedAccountsTotal <= 0 && $apOtherAmount <= 0) {
            throw new RuntimeException('Add a valid allocation, A/P other, or other account amount.');
        }

        $unallocatedAmount = round($amountPaid + $fixedAccountsTotal - $allocatedTotal - $apOtherAmount, 2);
        if (abs($unallocatedAmount) > 0.005) {
            throw new RuntimeException('Unallocated amount must be 0.00. Adjust allocations or other accounts.');
        }

        $range = $this->getActiveReceiptRange($userId);
        if (! $range) {
            throw new RuntimeException('Current user has no active receipt range.');
        }

        $payableModel = new PayableModel();
        $allocModel = new PayableAllocationModel();
        $rangeModel = new CashierReceiptRangeModel();
        $ledgerModel = new PayableLedgerModel();
        $db = db_connect();

        $db->transStart();

        $prNo = (int) $range['next_no'];
        $payableId = $payableModel->insert([
            'supplier_id' => $supplierId,
            'user_id' => $userId,
            'pr_no' => $prNo,
            'date' => $date,
            'method' => $method,
            'amount_received' => $amountPaid,
            'amount_allocated' => $allocatedTotal,
            'excess_used' => 0,
            'payer_bank' => $payerBank !== '' ? $payerBank : null,
            'check_no' => $checkNo !== '' ? $checkNo : null,
            'deposit_bank_id' => $depositBankId,
            'status' => 'posted',
        ], true);

        foreach ($cleanAllocations as $index => $allocation) {
            $cleanAllocations[$index]['payable_id'] = $payableId;
            $cleanAllocations[$index]['created_at'] = date('Y-m-d H:i:s');
        }

        if (! empty($cleanAllocations)) {
            $allocModel->insertBatch($cleanAllocations);
        }

        $this->insertLedgerRows(
            $ledgerModel,
            $supplierId,
            $date,
            $prNo,
            $payableId,
            $amountPaid,
            $allocatedTotal,
            $fixedAccountRows,
            $apOtherAmount,
            $apOtherDescription
        );

        $newNext = $prNo + 1;
        $rangeUpdate = ['next_no' => $newNext];
        if ($newNext > (int) $range['end_no']) {
            $rangeUpdate['status'] = 'closed';
        }
        $rangeModel->update($range['id'], $rangeUpdate);

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new RuntimeException('Failed to save payable.');
        }

        return [
            'payable_id' => $payableId,
            'supplier_id' => $supplierId,
            'pr_no' => $prNo,
        ];
    }

    private function normalizeFixedAccounts(array $rows): array
    {
        $cleanRows = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $cleanRows[] = [
                'title' => $title,
                'amount' => max(0.0, (float) ($row['amount'] ?? 0)),
            ];
        }

        return $cleanRows;
    }

    private function cleanAllocations(int $supplierId, array $allocations): array
    {
        $purchaseOrderIds = [];
        foreach ($allocations as $allocation) {
            $purchaseOrderId = (int) ($allocation['purchase_order_id'] ?? 0);
            if ($purchaseOrderId > 0) {
                $purchaseOrderIds[$purchaseOrderId] = true;
            }
        }

        $balances = $this->fetchPurchaseOrderBalancesByIds($supplierId, array_keys($purchaseOrderIds));
        $cleanAllocations = [];

        foreach ($allocations as $allocation) {
            $purchaseOrderId = (int) ($allocation['purchase_order_id'] ?? 0);
            $amount = (float) ($allocation['amount'] ?? 0);

            if ($purchaseOrderId <= 0 || $amount <= 0) {
                continue;
            }

            $balance = $balances[$purchaseOrderId] ?? null;
            if ($balance === null || $amount > $balance) {
                throw new RuntimeException('Allocation exceeds RR balance.');
            }

            $cleanAllocations[] = [
                'purchase_order_id' => $purchaseOrderId,
                'amount' => $amount,
            ];
        }

        return $cleanAllocations;
    }

    private function fetchPurchaseOrderBalancesByIds(int $supplierId, array $purchaseOrderIds): array
    {
        $purchaseOrderIds = array_values(array_unique(array_filter(array_map('intval', $purchaseOrderIds))));
        if (empty($purchaseOrderIds)) {
            return [];
        }

        $rows = db_connect()->table('purchase_orders po')
            ->select('po.id AS purchase_order_id')
            ->select('ROUND((po.total_amount - COALESCE(SUM(CASE WHEN p.status = "posted" THEN pa.amount ELSE 0 END), 0)), 2) AS balance', false)
            ->join('payable_allocations pa', 'pa.purchase_order_id = po.id', 'left')
            ->join('payables p', 'p.id = pa.payable_id', 'left')
            ->where('po.supplier_id', $supplierId)
            ->where('po.status !=', 'voided')
            ->whereIn('po.id', $purchaseOrderIds)
            ->groupBy('po.id')
            ->get()
            ->getResultArray();

        $balances = [];
        foreach ($rows as $row) {
            $purchaseOrderId = (int) ($row['purchase_order_id'] ?? 0);
            if ($purchaseOrderId > 0) {
                $balances[$purchaseOrderId] = (float) ($row['balance'] ?? 0);
            }
        }

        return $balances;
    }

    private function insertLedgerRows(
        PayableLedgerModel $ledgerModel,
        int $supplierId,
        string $date,
        int $prNo,
        int $payableId,
        float $amountPaid,
        float $allocatedTotal,
        array $fixedAccountRows,
        float $apOtherAmount,
        string $apOtherDescription
    ): void {
        $lastLedger = $ledgerModel
            ->select('balance')
            ->where('supplier_id', $supplierId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $currentBalance = (float) ($lastLedger['balance'] ?? 0);
        $ledgerPayment = min($amountPaid, $allocatedTotal);
        $currentBalance -= $ledgerPayment;

        $ledgerModel->insert([
            'supplier_id' => $supplierId,
            'entry_date' => $date,
            'po_no' => null,
            'pr_no' => (string) $prNo,
            'qty' => null,
            'price' => null,
            'payables' => 0,
            'payment' => $ledgerPayment,
            'account_title' => null,
            'other_accounts' => 0,
            'balance' => $currentBalance,
            'purchase_order_id' => null,
            'payable_id' => $payableId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($fixedAccountRows as $row) {
            $amount = (float) $row['amount'];
            if ($amount <= 0) {
                continue;
            }

            $currentBalance -= $amount;
            $ledgerModel->insert([
                'supplier_id' => $supplierId,
                'entry_date' => $date,
                'po_no' => null,
                'pr_no' => (string) $prNo,
                'qty' => null,
                'price' => null,
                'payables' => 0,
                'payment' => 0,
                'account_title' => $row['title'],
                'other_accounts' => $amount,
                'balance' => $currentBalance,
                'purchase_order_id' => null,
                'payable_id' => $payableId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($apOtherAmount > 0) {
            $currentBalance -= $apOtherAmount;
            $ledgerModel->insert([
                'supplier_id' => $supplierId,
                'entry_date' => $date,
                'po_no' => null,
                'pr_no' => (string) $prNo,
                'qty' => null,
                'price' => null,
                'payables' => 0,
                'payment' => 0,
                'account_title' => $apOtherDescription !== '' ? 'A/P Other: ' . $apOtherDescription : 'A/P Other',
                'other_accounts' => $apOtherAmount,
                'balance' => $currentBalance,
                'purchase_order_id' => null,
                'payable_id' => $payableId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
