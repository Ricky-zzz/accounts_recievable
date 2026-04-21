<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\BoaModel;
use App\Models\CashierModel;
use App\Models\CashierReceiptRangeModel;
use App\Models\ClientModel;
use App\Models\LedgerModel;
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

        if (! is_array($allocations) || empty($allocations)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one allocation.');
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

        if (empty($cleanAllocations)) {
            return redirect()->back()->withInput()->with('error', 'Add a valid allocation.');
        }

        $excessRow = $paymentModel
            ->select('SUM(amount_received - amount_allocated) as excess')
            ->where('client_id', $clientId)
            ->where('status', 'posted')
            ->first();
        $currentExcess = (float) ($excessRow['excess'] ?? 0);
        $availableExcess = max(0.0, $currentExcess);

        if ($allocatedTotal > ($amountReceived + $availableExcess)) {
            return redirect()->back()->withInput()->with('error', 'Allocations exceed amount received plus excess.');
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
        $paymentId = $paymentModel->insert([
            'client_id' => $clientId,
            'cashier_id' => $cashierId,
            'pr_no' => $prNo,
            'date' => $date,
            'method' => $method,
            'amount_received' => $amountReceived,
            'amount_allocated' => $allocatedTotal,
            'excess_used' => max(0.0, $allocatedTotal - $amountReceived),
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

        $boaModel->protect(false)->insert([
            'date' => $date,
            'payor' => $clientId,
            'reference' => $prNo,
            'payment_id' => $paymentId,
            'ar_trade' => $allocatedTotal,
            $boaColumn => $amountReceived,
        ]);

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
