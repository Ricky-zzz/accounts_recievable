<?php

namespace App\Services;

use App\Models\BankModel;
use App\Models\BoaModel;
use App\Models\CashierReceiptRangeModel;
use App\Models\ClientModel;
use App\Models\LedgerModel;
use App\Models\PaymentAllocationModel;
use App\Models\PaymentModel;
use RuntimeException;

class PaymentPostingService
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
        $clientId = (int) ($data['client_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $date = (string) ($data['date'] ?? '');
        $method = (string) ($data['method'] ?? '');
        $amountReceived = (float) ($data['amount_received'] ?? 0);
        $depositBankId = (int) ($data['deposit_bank_id'] ?? 0);
        $payerBank = trim((string) ($data['payer_bank'] ?? ''));
        $checkNo = trim((string) ($data['check_no'] ?? ''));
        $allocations = $data['allocations'] ?? [];
        $fixedAccountRows = $this->normalizeFixedAccounts($data['fixed_accounts'] ?? []);
        $arOtherDescription = trim((string) ($data['ar_other_description'] ?? ''));
        $arOtherAmount = max(0.0, (float) ($data['ar_other_amount'] ?? 0));

        if ($clientId <= 0 || $userId <= 0 || $date === '' || $amountReceived <= 0) {
            throw new RuntimeException('Please complete the payment form.');
        }

        if (! in_array($method, ['cash', 'bank', 'check'], true)) {
            throw new RuntimeException('Select a payment method.');
        }

        if ($depositBankId <= 0) {
            throw new RuntimeException('Select the deposit bank for this payment.');
        }

        if ($method === 'check' && ($checkNo === '' || $payerBank === '')) {
            throw new RuntimeException('Check number and payer bank are required.');
        }

        if ($method === 'bank' && $payerBank === '') {
            throw new RuntimeException('Payer bank is required.');
        }

        helper('boa');

        $depositBank = (new BankModel())->find($depositBankId);
        if (! $depositBank) {
            throw new RuntimeException('Selected deposit bank is invalid.');
        }

        $boaColumn = boa_column_from_bank_name((string) $depositBank['bank_name']);
        if ($boaColumn === '') {
            throw new RuntimeException('Selected deposit bank name is invalid for BOA.');
        }

        $db = db_connect();
        if (! $db->fieldExists($boaColumn, 'boa')) {
            throw new RuntimeException('Selected deposit bank is not yet linked to BOA.');
        }

        $fixedAccountsTotal = 0.0;
        foreach ($fixedAccountRows as $row) {
            $fixedAccountsTotal += (float) $row['amount'];
        }

        $cleanAllocations = $this->cleanAllocations($clientId, is_array($allocations) ? $allocations : []);
        $allocatedTotal = 0.0;
        foreach ($cleanAllocations as $allocation) {
            $allocatedTotal += (float) $allocation['amount'];
        }

        $unallocatedAmount = round($amountReceived + $fixedAccountsTotal - $allocatedTotal - $arOtherAmount, 2);
        if ($unallocatedAmount < -0.005) {
            throw new RuntimeException('Allocated amount exceeds the amount available on this receipt.');
        }

        $range = $this->getActiveReceiptRange($userId);
        if (! $range) {
            throw new RuntimeException('Current user has no active receipt range.');
        }

        $paymentModel = new PaymentModel();
        $allocModel = new PaymentAllocationModel();
        $rangeModel = new CashierReceiptRangeModel();
        $ledgerModel = new LedgerModel();
        $boaModel = new BoaModel();

        $db->transStart();

        $prNo = (int) $range['next_no'];
        $paymentId = $paymentModel->insert([
            'client_id' => $clientId,
            'user_id' => $userId,
            'pr_no' => $prNo,
            'date' => $date,
            'method' => $method,
            'amount_received' => $amountReceived,
            'amount_allocated' => $allocatedTotal,
            'excess_used' => 0,
            'payer_bank' => $payerBank !== '' ? $payerBank : null,
            'check_no' => $checkNo !== '' ? $checkNo : null,
            'deposit_bank_id' => $depositBankId,
            'status' => 'posted',
        ], true);

        if (! empty($cleanAllocations)) {
            foreach ($cleanAllocations as $index => $allocation) {
                $cleanAllocations[$index]['payment_id'] = $paymentId;
                $cleanAllocations[$index]['created_at'] = date('Y-m-d H:i:s');
            }
            $allocModel->insertBatch($cleanAllocations);
        }

        $this->insertLedgerRows($ledgerModel, $clientId, $date, $prNo, $paymentId, $amountReceived, $arOtherAmount, $fixedAccountRows);
        $this->insertBoaRows($boaModel, $clientId, $date, $prNo, $paymentId, $amountReceived, $allocatedTotal, $boaColumn, $fixedAccountRows, $arOtherAmount, $arOtherDescription);

        $newNext = $prNo + 1;
        $rangeUpdate = ['next_no' => $newNext];
        if ($newNext > (int) $range['end_no']) {
            $rangeUpdate['status'] = 'closed';
        }
        $rangeModel->update($range['id'], $rangeUpdate);

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new RuntimeException('Failed to save payment.');
        }

        return [
            'payment_id' => $paymentId,
            'client_id' => $clientId,
            'pr_no' => $prNo,
        ];
    }

    public function applyAdvance(array $data): array
    {
        $clientId = (int) ($data['client_id'] ?? 0);
        $paymentId = (int) ($data['payment_id'] ?? ($data['advance_payment_id'] ?? 0));
        $allocations = $data['allocations'] ?? [];

        if ($clientId <= 0 || $paymentId <= 0) {
            throw new RuntimeException('Select an advance PR to apply.');
        }

        $paymentModel = new PaymentModel();
        $payment = $paymentModel
            ->where('id', $paymentId)
            ->where('client_id', $clientId)
            ->where('status', 'posted')
            ->first();

        if (! $payment) {
            throw new RuntimeException('Selected advance PR was not found for this client.');
        }

        $cleanAllocations = $this->cleanAllocations($clientId, is_array($allocations) ? $allocations : []);
        if (empty($cleanAllocations)) {
            throw new RuntimeException('Add at least one DR allocation.');
        }

        $requestedTotal = 0.0;
        foreach ($cleanAllocations as $allocation) {
            $requestedTotal += (float) $allocation['amount'];
        }

        $advance = $this->advanceSummaryForPayment($paymentId);
        $advanceBalance = (float) ($advance['balance'] ?? 0);
        if ($requestedTotal > $advanceBalance + 0.005) {
            throw new RuntimeException('Allocation exceeds the remaining advance balance.');
        }

        $db = db_connect();
        $allocModel = new PaymentAllocationModel();
        $db->transStart();

        foreach ($cleanAllocations as $index => $allocation) {
            $cleanAllocations[$index]['payment_id'] = $paymentId;
            $cleanAllocations[$index]['created_at'] = date('Y-m-d H:i:s');
        }
        $allocModel->insertBatch($cleanAllocations);

        $allocatedTotal = $this->paymentAllocatedTotal($paymentId);
        $paymentModel->update($paymentId, ['amount_allocated' => $allocatedTotal]);
        $this->syncBoaArTrade($paymentId, $allocatedTotal);

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new RuntimeException('Failed to apply advance PR.');
        }

        return [
            'payment_id' => $paymentId,
            'client_id' => $clientId,
            'pr_no' => (int) ($payment['pr_no'] ?? 0),
        ];
    }

    public function advanceCollections(?int $clientId = null): array
    {
        return array_values(array_filter(
            $this->advanceSummaries($clientId),
            static fn (array $row): bool => (float) ($row['balance'] ?? 0) > 0.005
        ));
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

    private function cleanAllocations(int $clientId, array $allocations): array
    {
        $requestedByDelivery = [];
        foreach ($allocations as $allocation) {
            $deliveryId = (int) ($allocation['delivery_id'] ?? 0);
            $amount = (float) ($allocation['amount'] ?? 0);
            if ($deliveryId > 0 && $amount > 0) {
                $requestedByDelivery[$deliveryId] = ($requestedByDelivery[$deliveryId] ?? 0.0) + $amount;
            }
        }

        $deliveryBalances = $this->fetchDeliveryBalancesByIds($clientId, array_keys($requestedByDelivery));
        $cleanAllocations = [];

        foreach ($requestedByDelivery as $deliveryId => $amount) {
            $balance = $deliveryBalances[$deliveryId] ?? null;
            if ($balance === null || $amount > $balance + 0.005) {
                throw new RuntimeException('Allocation exceeds delivery balance.');
            }

            $cleanAllocations[] = [
                'delivery_id' => $deliveryId,
                'amount' => $amount,
            ];
        }

        return $cleanAllocations;
    }

    private function fetchDeliveryBalancesByIds(int $clientId, array $deliveryIds): array
    {
        $deliveryIds = array_values(array_unique(array_filter(array_map('intval', $deliveryIds))));
        if (empty($deliveryIds)) {
            return [];
        }

        $rows = db_connect()->table('deliveries d')
            ->select('d.id AS delivery_id')
            ->select('ROUND((d.total_amount - COALESCE(SUM(CASE WHEN p.status = "posted" THEN pa.amount ELSE 0 END), 0)), 2) AS balance', false)
            ->join('payment_allocations pa', 'pa.delivery_id = d.id', 'left')
            ->join('payments p', 'p.id = pa.payment_id', 'left')
            ->where('d.client_id', $clientId)
            ->whereIn('d.id', $deliveryIds)
            ->groupBy('d.id')
            ->get()
            ->getResultArray();

        $balances = [];
        foreach ($rows as $row) {
            $deliveryId = (int) ($row['delivery_id'] ?? 0);
            if ($deliveryId > 0) {
                $balances[$deliveryId] = (float) ($row['balance'] ?? 0);
            }
        }

        return $balances;
    }

    private function insertLedgerRows(
        LedgerModel $ledgerModel,
        int $clientId,
        string $date,
        int $prNo,
        int $paymentId,
        float $amountReceived,
        float $arOtherAmount,
        array $fixedAccountRows
    ): void {
        $currentBalance = $this->resolveStartingBalance($ledgerModel, $clientId);
        $ledgerCollection = max(0.0, $amountReceived - $arOtherAmount);
        $currentBalance -= $ledgerCollection;

        $ledgerModel->insert([
            'client_id' => $clientId,
            'entry_date' => $date,
            'dr_no' => null,
            'pr_no' => (string) $prNo,
            'qty' => null,
            'price' => null,
            'amount' => 0,
            'collection' => $ledgerCollection,
            'account_title' => null,
            'other_accounts' => 0,
            'balance' => $currentBalance,
            'delivery_id' => null,
            'payment_id' => $paymentId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($fixedAccountRows as $row) {
            $amount = (float) $row['amount'];
            if ($amount <= 0) {
                continue;
            }

            $currentBalance -= $amount;
            $ledgerModel->insert([
                'client_id' => $clientId,
                'entry_date' => $date,
                'dr_no' => null,
                'pr_no' => (string) $prNo,
                'qty' => null,
                'price' => null,
                'amount' => 0,
                'collection' => 0,
                'account_title' => $row['title'],
                'other_accounts' => $amount,
                'balance' => $currentBalance,
                'delivery_id' => null,
                'payment_id' => $paymentId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function advanceSummaryForPayment(int $paymentId): array
    {
        foreach ($this->advanceSummaries(null, $paymentId) as $row) {
            return $row;
        }

        return [];
    }

    private function advanceSummaries(?int $clientId = null, ?int $paymentId = null): array
    {
        $builder = db_connect()->table('payments p')
            ->select('p.id AS payment_id, p.client_id, p.pr_no, p.date, p.amount_received')
            ->select('COALESCE(pa.allocated_amount, 0) AS amount_allocated', false)
            ->select('COALESCE(ar.ar_other_amount, 0) AS ar_other_amount', false)
            ->select('COALESCE(fa.fixed_accounts_total, 0) AS fixed_accounts_total', false)
            ->join(
                '(SELECT payment_id, SUM(amount) AS allocated_amount FROM payment_allocations GROUP BY payment_id) pa',
                'pa.payment_id = p.id',
                'left'
            )
            ->join(
                '(SELECT payment_id, SUM(ar_others) AS ar_other_amount FROM boa GROUP BY payment_id) ar',
                'ar.payment_id = p.id',
                'left'
            )
            ->join(
                '(SELECT payment_id, SUM(dr) AS fixed_accounts_total FROM boa WHERE account_title IS NOT NULL GROUP BY payment_id) fa',
                'fa.payment_id = p.id',
                'left'
            )
            ->where('p.status', 'posted');

        if ($clientId !== null) {
            $builder->where('p.client_id', $clientId);
        }

        if ($paymentId !== null) {
            $builder->where('p.id', $paymentId);
        }

        $rows = $builder
            ->orderBy('p.date', 'asc')
            ->orderBy('p.id', 'asc')
            ->get()
            ->getResultArray();

        foreach ($rows as $index => $row) {
            $amountReceived = (float) ($row['amount_received'] ?? 0);
            $amountAllocated = (float) ($row['amount_allocated'] ?? 0);
            $arOtherAmount = (float) ($row['ar_other_amount'] ?? 0);
            $fixedAccountsTotal = (float) ($row['fixed_accounts_total'] ?? 0);
            $cashUsed = max(0.0, $amountAllocated + $arOtherAmount - $fixedAccountsTotal);

            $rows[$index]['balance'] = round($amountReceived - $cashUsed, 2);
        }

        return $rows;
    }

    private function paymentAllocatedTotal(int $paymentId): float
    {
        $row = db_connect()->table('payment_allocations')
            ->select('COALESCE(SUM(amount), 0) AS allocated_total', false)
            ->where('payment_id', $paymentId)
            ->get()
            ->getRowArray();

        return round((float) ($row['allocated_total'] ?? 0), 2);
    }

    private function syncBoaArTrade(int $paymentId, float $allocatedTotal): void
    {
        $db = db_connect();
        $row = $db->table('boa')
            ->select('id')
            ->where('payment_id', $paymentId)
            ->where('account_title IS NULL', null, false)
            ->where('(ar_others = 0 OR ar_others IS NULL)', null, false)
            ->orderBy('id', 'asc')
            ->get()
            ->getRowArray();

        if (! $row) {
            return;
        }

        $db->table('boa')
            ->where('id', (int) $row['id'])
            ->update(['ar_trade' => $allocatedTotal]);
    }

    private function resolveStartingBalance(LedgerModel $ledgerModel, int $clientId): float
    {
        $lastLedger = $ledgerModel
            ->select('balance')
            ->where('client_id', $clientId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLedger) {
            return (float) ($lastLedger['balance'] ?? 0);
        }

        $db = db_connect();
        if (! $db->fieldExists('forwarded_balance', 'clients')) {
            return 0.0;
        }

        $client = (new ClientModel())
            ->select('forwarded_balance')
            ->find($clientId);

        return (float) ($client['forwarded_balance'] ?? 0);
    }

    private function insertBoaRows(
        BoaModel $boaModel,
        int $clientId,
        string $date,
        int $prNo,
        int $paymentId,
        float $amountReceived,
        float $allocatedTotal,
        string $boaColumn,
        array $fixedAccountRows,
        float $arOtherAmount,
        string $arOtherDescription
    ): void {
        $boaModel->protect(false)->insert([
            'date' => $date,
            'payor' => $clientId,
            'reference' => (string) $prNo,
            'payment_id' => $paymentId,
            'ar_trade' => $allocatedTotal,
            $boaColumn => $amountReceived,
        ]);

        if ($arOtherAmount > 0) {
            $boaModel->protect(false)->insert([
                'date' => $date,
                'payor' => $clientId,
                'reference' => (string) $prNo,
                'payment_id' => $paymentId,
                'ar_others' => $arOtherAmount,
                'description' => $arOtherDescription !== '' ? $arOtherDescription : null,
            ]);
        }

        foreach ($fixedAccountRows as $row) {
            if ((float) $row['amount'] <= 0) {
                continue;
            }

            $boaModel->protect(false)->insert([
                'date' => $date,
                'payor' => $clientId,
                'reference' => (string) $prNo,
                'payment_id' => $paymentId,
                'account_title' => $row['title'],
                'dr' => $row['amount'],
                'cr' => 0,
            ]);
        }
    }
}
