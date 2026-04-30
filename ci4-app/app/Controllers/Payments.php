<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\ClientModel;
use App\Models\UserModel;
use App\Services\PaymentPostingService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class Payments extends BaseController
{
    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayments(null, $fromDate, $toDate, $prNo);

        return view('payments/index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
            'payments' => $result['payments'],
            'allocationsByPayment' => $result['allocationsByPayment'],
            'otherAccountsByPayment' => $result['otherAccountsByPayment'],
            'paymentsById' => $result['paymentsById'],
            'totalCollections' => $result['totalCollections'],
        ]);
    }

    public function print()
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayments(null, $fromDate, $toDate, $prNo);

        $html = view('payments/print', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
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
            ->setHeader('Content-Disposition', 'inline; filename="payments-report.pdf"')
            ->setBody($dompdf->output());
    }

    public function clientList(int $clientId): string
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($clientId);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayments($clientId, $fromDate, $toDate, $prNo);

        return view('payments/list', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
            'payments' => $result['payments'],
            'allocationsByPayment' => $result['allocationsByPayment'],
            'otherAccountsByPayment' => $result['otherAccountsByPayment'],
            'paymentsById' => $result['paymentsById'],
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
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayments($clientId, $fromDate, $toDate, $prNo);

        $html = view('payments/listprint', [
            'client' => $client,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
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

    private function resolvePrNoFilter(): string
    {
        return trim((string) ($this->request->getGet('pr_no') ?? ''));
    }

    public function createForm(int $clientId): string
    {
        $clientModel = new ClientModel();
        $userModel = new UserModel();
        $bankModel = new BankModel();
        $paymentPosting = new PaymentPostingService();

        $client = $clientModel->find($clientId);
        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId = (int) (session('user_id') ?? 0);
        $assignedUser = $userModel->find($userId);
        $activeRange = $paymentPosting->getActiveReceiptRange($userId);

        $unpaidPage = max(1, (int) $this->request->getGet('page') ?: 1);
        $unpaidPerPage = 15;
        $unpaidResult = $this->fetchUnpaidDeliveries($clientId, $unpaidPerPage, $unpaidPage);
        $pager = service('pager');

        return view('payments/form', [
            'client' => $client,
            'assignedUser' => $assignedUser,
            'activeReceipt' => $activeRange ? (int) $activeRange['next_no'] : null,
            'rangeEnd' => $activeRange ? (int) $activeRange['end_no'] : null,
            'banks' => $bankModel->orderBy('bank_name', 'asc')->findAll(),
            'unpaidDeliveries' => $unpaidResult['deliveries'],
            'unpaidPagerLinks' => $pager->makeLinks($unpaidResult['page'], $unpaidResult['perPage'], $unpaidResult['total'], 'default_full'),
        ]);
    }

    public function store()
    {
        try {
            $result = (new PaymentPostingService())->post($this->paymentPayloadFromRequest());
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('payments/client/' . $result['client_id'])->with('success', 'Payment saved.');
    }

    public function quickPay()
    {
        $payload = $this->paymentPayloadFromRequest();
        $payload['allocations'] = [
            [
                'delivery_id' => (int) $this->request->getPost('delivery_id'),
                'amount' => (float) $this->request->getPost('allocation_amount'),
            ],
        ];

        try {
            (new PaymentPostingService())->post($payload);
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Payment collected.');
    }

    private function paymentPayloadFromRequest(): array
    {
        return [
            'client_id' => (int) $this->request->getPost('client_id'),
            'user_id' => (int) (session('user_id') ?? 0),
            'date' => (string) $this->request->getPost('date'),
            'method' => (string) $this->request->getPost('method'),
            'amount_received' => (float) $this->request->getPost('amount_received'),
            'deposit_bank_id' => (int) $this->request->getPost('deposit_bank_id'),
            'payer_bank' => trim((string) $this->request->getPost('payer_bank')),
            'check_no' => trim((string) $this->request->getPost('check_no')),
            'allocations' => $this->request->getPost('allocations'),
            'ar_other_description' => trim((string) $this->request->getPost('ar_other_description')),
            'ar_other_amount' => (float) $this->request->getPost('ar_other_amount'),
            'fixed_accounts' => [
                [
                    'title' => 'Sales Discount',
                    'amount' => (float) $this->request->getPost('sales_discount'),
                ],
                [
                    'title' => 'Delivery Charges',
                    'amount' => (float) $this->request->getPost('delivery_charges'),
                ],
                [
                    'title' => 'Taxes',
                    'amount' => (float) $this->request->getPost('taxes'),
                ],
                [
                    'title' => 'Commissions',
                    'amount' => (float) $this->request->getPost('commissions'),
                ],
            ],
        ];
    }

    private function fetchUnpaidDeliveries(int $clientId, int $perPage = 15, int $page = 1): array
    {
        $db = db_connect();

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $baseSql = <<<SQL
SELECT
    d.id AS delivery_id,
    d.dr_no,
    d.date,
    d.due_date,
    d.total_amount,
    COALESCE(a.allocated_amount, 0) AS allocated_amount,
    ROUND((d.total_amount - COALESCE(a.allocated_amount, 0)), 2) AS balance
FROM deliveries d
LEFT JOIN (
    SELECT
        pa.delivery_id,
        SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END) AS allocated_amount
    FROM payment_allocations pa
    LEFT JOIN payments p ON p.id = pa.payment_id
    GROUP BY pa.delivery_id
) a ON a.delivery_id = d.id
WHERE d.client_id = ?
SQL;

        $countRow = $db->query(
            'SELECT COUNT(*) AS total FROM (' . $baseSql . ') unpaid_deliveries WHERE balance > 0',
            [$clientId]
        )->getRowArray();

        $total = (int) ($countRow['total'] ?? 0);

        $deliveries = $db->query(
            'SELECT * FROM (' . $baseSql . ') unpaid_deliveries WHERE balance > 0 ORDER BY date ASC, delivery_id ASC LIMIT ? OFFSET ?',
            [$clientId, $perPage, $offset]
        )->getResultArray();

        return [
            'deliveries' => $deliveries,
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }

    private function fetchPayments(?int $clientId, string $fromDate, string $toDate, string $prNo = ''): array
    {
        $db = db_connect();

        $builder = $db->table('payments p');
        $builder
            ->select('p.id, p.client_id, p.pr_no, p.date, p.amount_received')
            ->select('c.name as client_name')
            ->select('c.payment_term as client_payment_term')
            ->join('clients c', 'c.id = p.client_id', 'left')
            ->where('p.status', 'posted');

        if ($clientId !== null) {
            $builder->where('p.client_id', $clientId);
        }

        if ($prNo === '' && $fromDate !== '') {
            $builder->where('p.date >=', $fromDate);
        }

        if ($prNo === '' && $toDate !== '') {
            $builder->where('p.date <=', $toDate);
        }

        if ($prNo !== '') {
            $builder->like('p.pr_no', $prNo);
        }

        $payments = $builder
            ->orderBy('p.date', 'desc')
            ->orderBy('p.id', 'desc')
            ->get()
            ->getResultArray();

        $paymentIds = array_filter(array_map('intval', array_column($payments, 'id')));
        $allocationsByPayment = [];
        $otherAccountsByPayment = [];
        $paymentsById = [];

        foreach ($payments as $payment) {
            $paymentId = (int) ($payment['id'] ?? 0);
            if ($paymentId > 0) {
                $paymentsById[$paymentId] = $payment;
            }
        }

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

            $otherAccountRows = $db->table('boa b')
                ->select('b.payment_id, b.account_title, b.dr, b.ar_others, b.description, b.date, b.reference')
                ->whereIn('b.payment_id', $paymentIds)
                ->groupStart()
                    ->where('b.account_title IS NOT NULL', null, false)
                    ->orWhere('b.ar_others >', 0)
                ->groupEnd()
                ->orderBy('b.date', 'asc')
                ->orderBy('b.id', 'asc')
                ->get()
                ->getResultArray();

            foreach ($otherAccountRows as $row) {
                $paymentId = (int) ($row['payment_id'] ?? 0);
                if ($paymentId > 0) {
                    $otherAccountsByPayment[$paymentId][] = $row;
                }
            }
        }

        $totalCollections = 0.0;
        foreach ($payments as $payment) {
            $totalCollections += (float) $payment['amount_received'];
        }

        return [
            'payments' => $payments,
            'allocationsByPayment' => $allocationsByPayment,
            'otherAccountsByPayment' => $otherAccountsByPayment,
            'paymentsById' => $paymentsById,
            'totalCollections' => $totalCollections,
        ];
    }
}
