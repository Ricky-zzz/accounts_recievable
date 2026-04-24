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
use Dompdf\Dompdf;
use Dompdf\Options;

class Payments extends BaseController
{
    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $result = $this->fetchPayments(null, $fromDate, $toDate);

        return view('payments/index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'payments' => $result['payments'],
            'allocationsByPayment' => $result['allocationsByPayment'],
            'totalCollections' => $result['totalCollections'],
        ]);
    }

    public function clientList(int $clientId): string
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $result = $this->fetchPayments($clientId, $fromDate, $toDate);

        return view('payments/list', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'payments' => $result['payments'],
            'allocationsByPayment' => $result['allocationsByPayment'],
            'totalCollections' => $result['totalCollections'],
        ]);
    }

    public function listPrint(int $clientId)
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $result = $this->fetchPayments($clientId, $fromDate, $toDate);

        $html = view('payments/listprint', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'payments' => $result['payments'],
            'totalCollections' => $result['totalCollections'],
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="payments-list-report.pdf"')
            ->setBody($dompdf->output());
    }

    private function resolveDateRange(): array
    {
        $fromDate = trim((string) ($this->request->getGet('from_date') ?? ''));
        $toDate = trim((string) ($this->request->getGet('to_date') ?? ''));

        if ($fromDate === '' && $toDate === '') {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
        }

        return [$fromDate, $toDate];
    }

    public function createForm(int $clientId): string
    {
        $clientModel = new ClientModel();
        $cashierModel = new CashierModel();
        $rangeModel = new CashierReceiptRangeModel();
        $bankModel = new BankModel();

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

        $unpaidDeliveries = $this->fetchUnpaidDeliveries($clientId);

        return view('payments/form', [
            'client' => $client,
            'cashiers' => $cashierData,
            'banks' => $bankModel->orderBy('bank_name', 'asc')->findAll(),
            'unpaidDeliveries' => $unpaidDeliveries,
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
        $arOtherDescription = trim((string) $this->request->getPost('ar_other_description'));
        $arOtherAmount = (float) $this->request->getPost('ar_other_amount');
        $salesDiscount = (float) $this->request->getPost('sales_discount');
        $deliveryCharges = (float) $this->request->getPost('delivery_charges');
        $taxes = (float) $this->request->getPost('taxes');
        $commissions = (float) $this->request->getPost('commissions');

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

        $fixedAccountRows = [
            [
                'title' => 'Sales Discount',
                'amount' => max(0.0, $salesDiscount),
            ],
            [
                'title' => 'Delivery Charges',
                'amount' => max(0.0, $deliveryCharges),
            ],
            [
                'title' => 'Taxes',
                'amount' => max(0.0, $taxes),
            ],
            [
                'title' => 'Commissions',
                'amount' => max(0.0, $commissions),
            ],
        ];
        $fixedAccountsTotal = 0.0;
        foreach ($fixedAccountRows as $row) {
            $fixedAccountsTotal += (float) $row['amount'];
        }
        $arOtherAmount = max(0.0, $arOtherAmount);

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

        if (empty($cleanAllocations) && $fixedAccountsTotal <= 0 && $arOtherAmount <= 0) {
            return redirect()->back()->withInput()->with('error', 'Add a valid allocation, A/R other, or other account amount.');
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
            'excess_used' => 0,
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

        $arTradeTotal = $allocatedTotal - $fixedAccountsTotal;

        $boaModel->protect(false)->insert([
            'date' => $date,
            'payor' => $clientId,
            'reference' => (string) $prNo,
            'payment_id' => $paymentId,
            'ar_trade' => $arTradeTotal,
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

        return redirect()->to('payments/client/' . $clientId)->with('success', 'Payment saved.');
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

    private function fetchPayments(?int $clientId, string $fromDate, string $toDate): array
    {
        $db = db_connect();

        $builder = $db->table('payments p');
        $builder
            ->select('p.id, p.client_id, p.pr_no, p.date, p.amount_received')
            ->where('p.status', 'posted');

        if ($clientId !== null) {
            $builder->where('p.client_id', $clientId);
        }

        if ($fromDate !== '') {
            $builder->where('p.date >=', $fromDate);
        }

        if ($toDate !== '') {
            $builder->where('p.date <=', $toDate);
        }

        $payments = $builder
            ->orderBy('p.date', 'desc')
            ->orderBy('p.id', 'desc')
            ->get()
            ->getResultArray();

        $paymentIds = array_filter(array_map('intval', array_column($payments, 'id')));
        $allocationsByPayment = [];

        if (! empty($paymentIds)) {
            $allocations = $db->table('payment_allocations pa')
                ->select('pa.payment_id, pa.amount, d.dr_no, d.date')
                ->join('deliveries d', 'd.id = pa.delivery_id', 'left')
                ->whereIn('pa.payment_id', $paymentIds)
                ->orderBy('d.date', 'asc')
                ->get()
                ->getResultArray();

            foreach ($allocations as $allocation) {
                $paymentId = (int) $allocation['payment_id'];
                $allocationsByPayment[$paymentId][] = $allocation;
            }
        }

        $totalCollections = 0.0;
        foreach ($payments as $payment) {
            $totalCollections += (float) $payment['amount_received'];
        }

        return [
            'payments' => $payments,
            'allocationsByPayment' => $allocationsByPayment,
            'totalCollections' => $totalCollections,
        ];
    }
}
