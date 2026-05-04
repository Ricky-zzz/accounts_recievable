<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\SupplierModel;
use App\Models\UserModel;
use App\Services\PayablePostingService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class Payables extends BaseController
{
    public function index(): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayables(null, $fromDate, $toDate, $prNo);

        return view('payables/index', [
            'supplier' => null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
            'payables' => $result['payables'],
            'payablesById' => $result['payablesById'],
            'totalPayments' => $result['totalPayments'],
        ]);
    }

    public function supplierList(int $supplierId): string
    {
        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayables($supplierId, $fromDate, $toDate, $prNo);

        return view('payables/list', [
            'supplier' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
            'payables' => $result['payables'],
            'payablesById' => $result['payablesById'],
            'totalPayments' => $result['totalPayments'],
        ]);
    }

    public function print()
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayables(null, $fromDate, $toDate, $prNo);

        return $this->renderPrintPdf([
            'supplier' => null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
            'payables' => $result['payables'],
            'totalPayments' => $result['totalPayments'],
        ], 'payables-report.pdf');
    }

    public function supplierPrint(int $supplierId)
    {
        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        $prNo = $this->resolvePrNoFilter();
        $result = $this->fetchPayables($supplierId, $fromDate, $toDate, $prNo);

        return $this->renderPrintPdf([
            'supplier' => $supplier,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'prNo' => $prNo,
            'payables' => $result['payables'],
            'totalPayments' => $result['totalPayments'],
        ], 'supplier-payables-report.pdf');
    }

    private function renderPrintPdf(array $data, string $filename)
    {
        $html = view('payables/print', $data);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    public function createForm(int $supplierId): string
    {
        $supplier = (new SupplierModel())->find($supplierId);
        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId = (int) (session('user_id') ?? 0);
        $payablePosting = new PayablePostingService();
        $activeRange = $payablePosting->getActiveReceiptRange($userId);
        $unpaidPage = max(1, (int) $this->request->getGet('page') ?: 1);
        $unpaidPerPage = 15;
        $unpaidResult = $this->fetchUnpaidPurchaseOrders($supplierId, $unpaidPerPage, $unpaidPage);

        return view('payables/form', [
            'supplier' => $supplier,
            'assignedUser' => (new UserModel())->find($userId),
            'activeReceipt' => $activeRange ? (int) $activeRange['next_no'] : null,
            'rangeEnd' => $activeRange ? (int) $activeRange['end_no'] : null,
            'banks' => (new BankModel())->orderBy('bank_name', 'asc')->findAll(),
            'unpaidPurchaseOrders' => $unpaidResult['purchaseOrders'],
            'unpaidPagerLinks' => service('pager')->makeLinks($unpaidResult['page'], $unpaidResult['perPage'], $unpaidResult['total'], 'default_full'),
        ]);
    }

    public function store()
    {
        try {
            $result = (new PayablePostingService())->post($this->payablePayloadFromRequest());
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('payables/supplier/' . $result['supplier_id'])->with('success', 'Payable saved.');
    }

    public function quickPay()
    {
        $payload = $this->payablePayloadFromRequest();
        $payload['allocations'] = [
            [
                'purchase_order_id' => (int) $this->request->getPost('purchase_order_id'),
                'amount' => (float) $this->request->getPost('allocation_amount'),
            ],
        ];

        try {
            (new PayablePostingService())->post($payload);
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Payable posted.');
    }

    private function payablePayloadFromRequest(): array
    {
        return [
            'supplier_id' => (int) $this->request->getPost('supplier_id'),
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
                ['title' => 'Purchase Discount', 'amount' => (float) $this->request->getPost('sales_discount')],
                ['title' => 'Delivery Charges', 'amount' => (float) $this->request->getPost('delivery_charges')],
                ['title' => 'Taxes', 'amount' => (float) $this->request->getPost('taxes')],
                ['title' => 'Commissions', 'amount' => (float) $this->request->getPost('commissions')],
            ],
        ];
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

    private function fetchUnpaidPurchaseOrders(int $supplierId, int $perPage = 15, int $page = 1): array
    {
        $db = db_connect();
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $baseSql = <<<SQL
SELECT
    po.id AS purchase_order_id,
    po.po_no,
    po.date,
    po.due_date,
    po.total_amount,
    COALESCE(a.allocated_amount, 0) AS allocated_amount,
    ROUND((po.total_amount - COALESCE(a.allocated_amount, 0)), 2) AS balance
FROM purchase_orders po
LEFT JOIN (
    SELECT
        pa.purchase_order_id,
        SUM(CASE WHEN p.status = 'posted' THEN pa.amount ELSE 0 END) AS allocated_amount
    FROM payable_allocations pa
    LEFT JOIN payables p ON p.id = pa.payable_id
    GROUP BY pa.purchase_order_id
) a ON a.purchase_order_id = po.id
WHERE po.supplier_id = ?
AND po.status != 'voided'
SQL;

        $countRow = $db->query(
            'SELECT COUNT(*) AS total FROM (' . $baseSql . ') unpaid_purchase_orders WHERE balance > 0',
            [$supplierId]
        )->getRowArray();

        $purchaseOrders = $db->query(
            'SELECT * FROM (' . $baseSql . ') unpaid_purchase_orders WHERE balance > 0 ORDER BY date ASC, purchase_order_id ASC LIMIT ? OFFSET ?',
            [$supplierId, $perPage, $offset]
        )->getResultArray();

        return [
            'purchaseOrders' => $purchaseOrders,
            'total' => (int) ($countRow['total'] ?? 0),
            'perPage' => $perPage,
            'page' => $page,
        ];
    }

    private function fetchPayables(?int $supplierId, string $fromDate, string $toDate, string $prNo): array
    {
        $db = db_connect();
        $builder = $db->table('payables p');
        $builder
            ->select('p.id, p.supplier_id, p.pr_no, p.date, p.amount_received, p.amount_allocated')
            ->select('s.name as supplier_name')
            ->join('suppliers s', 's.id = p.supplier_id', 'left')
            ->where('p.status', 'posted');

        if ($supplierId !== null) {
            $builder->where('p.supplier_id', $supplierId);
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

        $payables = $builder
            ->orderBy('p.date', 'desc')
            ->orderBy('p.id', 'desc')
            ->get()
            ->getResultArray();

        $payablesById = [];

        foreach ($payables as $payable) {
            $payableId = (int) ($payable['id'] ?? 0);
            if ($payableId > 0) {
                $payablesById[$payableId] = $payable;
            }
        }

        $totalPayments = 0.0;
        foreach ($payables as $payable) {
            $totalPayments += (float) $payable['amount_received'];
        }

        return [
            'payables' => $payables,
            'payablesById' => $payablesById,
            'totalPayments' => $totalPayments,
        ];
    }
}
