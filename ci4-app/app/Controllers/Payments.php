<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\BoaModel;
use App\Models\CashierModel;
use App\Models\CashierReceiptRangeModel;
use App\Models\ClientModel;
use App\Models\LedgerModel;
use App\Models\OtherAccountModel;
use App\Models\PaymentAllocationModel;
use App\Models\PaymentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Payments extends BaseController
{
    public function index(): string
    {
        $clientModel = new ClientModel();
        $query = trim((string) $this->request->getGet('q'));
        $builder = $clientModel->orderBy('name', 'asc');

        if ($query !== '') {
            $builder
                ->groupStart()
                ->like('name', $query)
                ->orLike('email', $query)
                ->orLike('phone', $query)
                ->groupEnd();
        }

        return view('payments/index', [
            'clients' => $builder->findAll(),
            'query' => $query,
        ]);
    }

    public function createForm(int $clientId): string
    {
        $clientModel = new ClientModel();
        $cashierModel = new CashierModel();
        $rangeModel = new CashierReceiptRangeModel();
        $bankModel = new BankModel();
        $paymentModel = new PaymentModel();
        $otherAccountModel = new OtherAccountModel();

        $client = $clientModel->find($clientId);
        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $cashiers = $cashierModel->orderBy('name', 'asc')->findAll();
        $ranges = $rangeModel->where('status', 'active')->findAll();

        $rangeByCashier = [];
        foreach ($ranges as $range) {
            $rangeByCashier[$range['cashier_id']] = $range;
        }

        $cashierData = [];
        foreach ($cashiers as $cashier) {
            $range = $rangeByCashier[$cashier['id']] ?? null;
            $hasActive = $range && (int) $range['next_no'] <= (int) $range['end_no'];

            $cashierData[] = [
                'id' => $cashier['id'],
                'name' => $cashier['name'],
                'active_receipt' => $hasActive ? (int) $range['next_no'] : null,
                'range_end' => $hasActive ? (int) $range['end_no'] : null,
            ];
        }

        $excessRow = $paymentModel
            ->select('SUM(amount_received - amount_allocated) as excess')
            ->where('client_id', $clientId)
            ->where('status', 'posted')
            ->first();
        $currentExcess = (float) ($excessRow['excess'] ?? 0);

        $unpaidDeliveries = $this->fetchUnpaidDeliveries($clientId);

        return view('payments/form', [
            'client' => $client,
            'cashiers' => $cashierData,
            'banks' => $bankModel->orderBy('bank_name', 'asc')->findAll(),
            'unpaidDeliveries' => $unpaidDeliveries,
            'currentExcess' => $currentExcess,
            'otherAccounts' => $otherAccountModel->orderBy('name', 'asc')->findAll(),
        ]);
    }

    public function store()
    {
        $clientId = (int) $this->request->getPost('client_id');
        $cashierId = (int) $this->request->getPost('cashier_id');
        $date = (string) $this->request->getPost('date');
        $method = (string) $this->request->getPost('method');
        $amountReceived = (float) $this->request->getPost('amount_received');
        $depositBankId = $this->request->getPost('deposit_bank_id');
        $payerBank = trim((string) $this->request->getPost('payer_bank'));
        $checkNo = trim((string) $this->request->getPost('check_no'));
        $allocations = $this->request->getPost('allocations');
        $otherAccountRows = $this->request->getPost('other_accounts');
        $arOtherRows = $this->request->getPost('ar_others');

        if ($clientId <= 0 || $cashierId <= 0 || $date === '' || $amountReceived <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please complete the payment form.');
        }

        if (! in_array($method, ['cash', 'bank', 'check'], true)) {
            return redirect()->back()->withInput()->with('error', 'Select a payment method.');
        }

        if ((int) $depositBankId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Select the deposit bank for this payment.');
        }

        if ($method === 'check' && ($checkNo === '' || $payerBank === '')) {
            return redirect()->back()->withInput()->with('error', 'Check number and payer bank are required.');
        }

        if ($method === 'bank' && $payerBank === '') {
            return redirect()->back()->withInput()->with('error', 'Payer bank is required.');
        }

        $allocations = is_array($allocations) ? $allocations : [];

        $otherAccountsData = [];
        $otherDrTotal = 0.0;
        $otherCrTotal = 0.0;
        $otherDrAffectTotal = 0.0;
        $otherCrAffectTotal = 0.0;

        if (is_array($otherAccountRows) && ! empty($otherAccountRows)) {
            $accountIds = [];
            foreach ($otherAccountRows as $row) {
                $accountIds[] = (int) ($row['account_id'] ?? 0);
            }

            $accountIds = array_values(array_filter(array_unique($accountIds)));
            if (! empty($accountIds)) {
                $otherAccountModel = new OtherAccountModel();
                $accounts = $otherAccountModel->whereIn('id', $accountIds)->findAll();
                $accountMap = [];
                foreach ($accounts as $account) {
                    $accountMap[(int) $account['id']] = $account;
                }

                foreach ($otherAccountRows as $row) {
                    $accountId = (int) ($row['account_id'] ?? 0);
                    $amount = (float) ($row['amount'] ?? 0);
                    if ($accountId <= 0 || $amount <= 0) {
                        continue;
                    }

                    $account = $accountMap[$accountId] ?? null;
                    if (! $account) {
                        return redirect()->back()->withInput()->with('error', 'Selected other account not found.');
                    }

                    $type = (string) ($account['type'] ?? '');
                    if (! in_array($type, ['dr', 'cr'], true)) {
                        return redirect()->back()->withInput()->with('error', 'Invalid other account type.');
                    }

                    $reference = trim((string) ($row['reference'] ?? ''));
                    $note = trim((string) ($row['note'] ?? ''));
                    $affectsTrade = ((string) ($row['affects_trade'] ?? '0')) === '1';

                    $otherAccountsData[] = [
                        'account_title' => (string) $account['name'],
                        'type' => $type,
                        'amount' => $amount,
                        'reference' => $reference,
                        'note' => $note,
                        'affects_trade' => $affectsTrade,
                    ];

                    if ($type === 'dr') {
                        $otherDrTotal += $amount;
                        if ($affectsTrade) {
                            $otherDrAffectTotal += $amount;
                        }
                    } else {
                        $otherCrTotal += $amount;
                        if ($affectsTrade) {
                            $otherCrAffectTotal += $amount;
                        }
                    }
                }
            }
        }

        $arOtherData = [];
        $arOtherTotal = 0.0;
        $arOtherAffectTotal = 0.0;

        if (is_array($arOtherRows) && ! empty($arOtherRows)) {
            foreach ($arOtherRows as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }
                $description = trim((string) ($row['description'] ?? ''));
                $affectsTrade = ((string) ($row['affects_trade'] ?? '0')) === '1';

                $arOtherData[] = [
                    'description' => $description,
                    'amount' => $amount,
                    'affects_trade' => $affectsTrade,
                ];

                $arOtherTotal += $amount;
                if ($affectsTrade) {
                    $arOtherAffectTotal += $amount;
                }
            }
        }

        helper('boa');

        $bankModel = new BankModel();
        $depositBank = $bankModel->find((int) $depositBankId);
        if (! $depositBank) {
            return redirect()->back()->withInput()->with('error', 'Selected deposit bank is invalid.');
        }

        $boaColumn = boa_column_from_bank_name((string) $depositBank['bank_name']);
        if ($boaColumn === '') {
            return redirect()->back()->withInput()->with('error', 'Selected deposit bank name is invalid for BOA.');
        }

        $paymentModel = new PaymentModel();
        $allocModel = new PaymentAllocationModel();
        $rangeModel = new CashierReceiptRangeModel();
        $ledgerModel = new LedgerModel();
        $boaModel = new BoaModel();

        $unpaidDeliveries = $this->fetchUnpaidDeliveries($clientId);
        $deliveryBalances = [];
        foreach ($unpaidDeliveries as $delivery) {
            $deliveryBalances[(int) $delivery['id']] = (float) $delivery['balance'];
        }

        $cleanAllocations = [];
        $allocatedTotal = 0.0;

        foreach ($allocations as $allocation) {
            $deliveryId = (int) ($allocation['delivery_id'] ?? 0);
            $amount = (float) ($allocation['amount'] ?? 0);

            if ($deliveryId <= 0 || $amount <= 0) {
                continue;
            }

            $balance = $deliveryBalances[$deliveryId] ?? null;
            if ($balance === null || $amount > $balance) {
                return redirect()->back()->withInput()->with('error', 'Allocation exceeds delivery balance.');
            }

            $cleanAllocations[] = [
                'delivery_id' => $deliveryId,
                'amount' => $amount,
            ];
            $allocatedTotal += $amount;
        }

        if (empty($cleanAllocations) && empty($otherAccountsData) && empty($arOtherData)) {
            return redirect()->back()->withInput()->with('error', 'Add a valid allocation or other entries.');
        }

        $excessRow = $paymentModel
            ->select('SUM(amount_received - amount_allocated) as excess')
            ->where('client_id', $clientId)
            ->where('status', 'posted')
            ->first();
        $currentExcess = (float) ($excessRow['excess'] ?? 0);
        $availableExcess = max(0.0, $currentExcess);

        if ($allocatedTotal > ($amountReceived + $availableExcess + $otherDrAffectTotal)) {
            return redirect()->back()->withInput()->with('error', 'Allocations exceed amount received plus excess and DR adjustments.');
        }

        $range = $rangeModel
            ->where('cashier_id', $cashierId)
            ->where('status', 'active')
            ->first();

        if (! $range || (int) $range['next_no'] > (int) $range['end_no']) {
            return redirect()->back()->withInput()->with('error', 'Cashier has no active receipt range.');
        }

        $db = db_connect();

        if (! $db->fieldExists($boaColumn, 'boa')) {
            return redirect()->back()->withInput()->with('error', 'Selected deposit bank is not yet linked to BOA.');
        }

        $db->transStart();

        $prNo = (int) $range['next_no'];
        $excessUsed = max(0.0, min($availableExcess, $allocatedTotal - $amountReceived - $otherDrAffectTotal));

        $paymentId = $paymentModel->insert([
            'client_id' => $clientId,
            'cashier_id' => $cashierId,
            'pr_no' => $prNo,
            'date' => $date,
            'method' => $method,
            'amount_received' => $amountReceived,
            'amount_allocated' => $allocatedTotal,
            'excess_used' => $excessUsed,
            'payer_bank' => $payerBank !== '' ? $payerBank : null,
            'check_no' => $checkNo !== '' ? $checkNo : null,
            'deposit_bank_id' => (int) $depositBankId ?: null,
            'status' => 'posted',
        ], true);

        foreach ($cleanAllocations as $index => $allocation) {
            $cleanAllocations[$index]['payment_id'] = $paymentId;
            $cleanAllocations[$index]['created_at'] = date('Y-m-d H:i:s');
        }
        $allocModel->insertBatch($cleanAllocations);

        $lastLedger = $ledgerModel
            ->select('balance')
            ->where('client_id', $clientId)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $previousBalance = (float) ($lastLedger['balance'] ?? 0);

        $ledgerModel->insert([
            'client_id' => $clientId,
            'entry_date' => $date,
            'dr_no' => null,
            'pr_no' => (string) $prNo,
            'qty' => null,
            'price' => null,
            'amount' => 0,
            'collection' => $allocatedTotal,
            'balance' => $previousBalance - $allocatedTotal,
            'delivery_id' => null,
            'payment_id' => $paymentId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $arTradeTotal = $allocatedTotal + $otherDrAffectTotal - $otherCrAffectTotal + $arOtherAffectTotal;

        $boaModel->protect(false)->insert([
            'date' => $date,
            'payor' => $clientId,
            'reference' => (string) $prNo,
            'payment_id' => $paymentId,
            'ar_trade' => $arTradeTotal,
            $boaColumn => $amountReceived,
        ]);

        foreach ($otherAccountsData as $row) {
            $boaModel->protect(false)->insert([
                'date' => $date,
                'payor' => $clientId,
                'reference' => $row['reference'] !== '' ? $row['reference'] : null,
                'payment_id' => $paymentId,
                'account_title' => $row['account_title'],
                'note' => $row['note'] !== '' ? $row['note'] : null,
                'dr' => $row['type'] === 'dr' ? $row['amount'] : 0,
                'cr' => $row['type'] === 'cr' ? $row['amount'] : 0,
            ]);
        }

        foreach ($arOtherData as $row) {
            $boaModel->protect(false)->insert([
                'date' => $date,
                'payor' => $clientId,
                'reference' => (string) $prNo,
                'payment_id' => $paymentId,
                'ar_others' => $row['amount'],
                'description' => $row['description'] !== '' ? $row['description'] : null,
            ]);
        }

        $newNext = $prNo + 1;
        $rangeUpdate = [
            'next_no' => $newNext,
        ];
        if ($newNext > (int) $range['end_no']) {
            $rangeUpdate['status'] = 'closed';
        }
        $rangeModel->update($range['id'], $rangeUpdate);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to save payment.');
        }

        return redirect()->to('/payments')->with('success', 'Payment saved.');
    }

    private function fetchUnpaidDeliveries(int $clientId): array
    {
        $db = db_connect();
        $builder = $db->table('deliveries d');

        $builder
            ->select('d.id, d.dr_no, d.date, d.total_amount')
            ->select('COALESCE(SUM(CASE WHEN p.status = "posted" THEN pa.amount ELSE 0 END), 0) as allocated_amount')
            ->select('(d.total_amount - COALESCE(SUM(CASE WHEN p.status = "posted" THEN pa.amount ELSE 0 END), 0)) as balance')
            ->join('payment_allocations pa', 'pa.delivery_id = d.id', 'left')
            ->join('payments p', 'p.id = pa.payment_id', 'left')
            ->where('d.client_id', $clientId)
            ->groupBy('d.id')
            ->having('balance >', 0)
            ->orderBy('d.date', 'asc');

        return $builder->get()->getResultArray();
    }
}
