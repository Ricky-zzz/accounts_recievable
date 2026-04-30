<?php

namespace App\Controllers;

use App\Models\SupplierModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class Suppliers extends BaseController
{
    private function redirectWithFormState(string $message, string $mode, ?int $id = null, array $errors = [])
    {
        $redirect = redirect()
            ->to('/suppliers')
            ->withInput()
            ->with('error', $message)
            ->with('form_mode', $mode)
            ->with('form_errors', $errors);

        if ($id !== null) {
            $redirect = $redirect->with('form_id', $id);
        }

        return $redirect;
    }

    public function index(): string
    {
        $model = new SupplierModel();
        $query = trim((string) $this->request->getGet('q'));
        $builder = $model->orderBy('name', 'asc');

        if ($query !== '') {
            $builder
                ->groupStart()
                ->like('name', $query)
                ->orLike('email', $query)
                ->orLike('phone', $query)
                ->groupEnd();
        }

        $suppliers = $builder->paginate(15);
        $supplierIds = array_map(static fn (array $supplier): int => (int) $supplier['id'], $suppliers);
        $balancesBySupplier = [];

        if (! empty($supplierIds)) {
            $ledgerRows = db_connect()->table('payable_ledger pl')
                ->select('pl.supplier_id, pl.balance')
                ->whereIn('pl.supplier_id', $supplierIds)
                ->orderBy('pl.supplier_id', 'asc')
                ->orderBy('pl.entry_date', 'desc')
                ->orderBy('pl.id', 'desc')
                ->get()
                ->getResultArray();

            foreach ($ledgerRows as $row) {
                $supplierId = (int) ($row['supplier_id'] ?? 0);
                if ($supplierId > 0 && ! array_key_exists($supplierId, $balancesBySupplier)) {
                    $balancesBySupplier[$supplierId] = (float) ($row['balance'] ?? 0);
                }
            }
        }

        foreach ($suppliers as $index => $supplier) {
            $supplierId = (int) ($supplier['id'] ?? 0);
            $creditLimit = isset($supplier['credit_limit']) ? (float) $supplier['credit_limit'] : 0.0;
            $currentBalance = $balancesBySupplier[$supplierId] ?? 0.0;

            $suppliers[$index]['current_balance'] = $currentBalance;
            $suppliers[$index]['available_credit'] = $creditLimit - $currentBalance;
        }

        return view('suppliers/index', [
            'suppliers' => $suppliers,
            'pager' => $model->pager,
            'query' => $query,
        ]);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]',
            'email' => 'permit_empty|valid_email',
            'phone' => 'permit_empty|max_length[50]',
            'credit_limit' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'payment_term' => 'permit_empty|is_natural',
        ];

        $supplier = $this->supplierPayloadFromRequest();

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'create',
                null,
                $this->validator->getErrors()
            );
        }

        (new SupplierModel())->insert($supplier);

        return redirect()->to('/suppliers')->with('success', 'Supplier created.');
    }

    public function update(int $id)
    {
        $model = new SupplierModel();
        $existing = $model->find($id);

        if (! $existing) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'name' => 'required|min_length[2]',
            'email' => 'permit_empty|valid_email',
            'phone' => 'permit_empty|max_length[50]',
            'credit_limit' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'payment_term' => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'edit',
                $id,
                $this->validator->getErrors()
            );
        }

        $model->update($id, $this->supplierPayloadFromRequest());

        return redirect()->to('/suppliers')->with('success', 'Supplier updated.');
    }

    public function delete(int $id)
    {
        (new SupplierModel())->delete($id);

        return redirect()->to('/suppliers')->with('success', 'Supplier deleted.');
    }

    public function payablesStatement(int $id)
    {
        $model = new SupplierModel();
        $supplier = $model->find($id);

        if (! $supplier) {
            throw PageNotFoundException::forPageNotFound();
        }

        $start = trim((string) ($this->request->getGet('start') ?? ''));
        $end = trim((string) ($this->request->getGet('end') ?? ''));
        $dueDate = trim((string) ($this->request->getGet('due_date') ?? ''));

        if ($start === '') {
            $start = date('Y-m-01');
        }

        if ($end === '') {
            $end = date('Y-m-t');
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $db = db_connect();
        $postedAllocations = $db->table('payable_allocations pa')
            ->select('pa.purchase_order_id, SUM(pa.amount) as allocated_amount')
            ->join('payables p', 'p.id = pa.payable_id', 'inner')
            ->where('p.status', 'posted')
            ->groupBy('pa.purchase_order_id')
            ->getCompiledSelect();

        $rows = $db->table('purchase_orders po')
            ->select('po.date as entry_date, po.po_no, po.due_date, po.total_amount as amount')
            ->select('COALESCE(payments_summary.allocated_amount, 0) as payment')
            ->select('(po.total_amount - COALESCE(payments_summary.allocated_amount, 0)) as balance')
            ->join("({$postedAllocations}) payments_summary", 'payments_summary.purchase_order_id = po.id', 'left')
            ->where('po.supplier_id', $id)
            ->where('po.status', 'active')
            ->where('po.voided_at', null)
            ->where('po.date <=', date('Y-m-d'))
            ->having('balance >', 0)
            ->orderBy('po.date', 'asc')
            ->orderBy('po.id', 'asc')
            ->get()
            ->getResultArray();

        $totalPayables = 0.0;
        $totalPayments = 0.0;
        $endingBalance = 0.0;

        foreach ($rows as $row) {
            $totalPayables += (float) ($row['amount'] ?? 0);
            $totalPayments += (float) ($row['payment'] ?? 0);
            $endingBalance += (float) ($row['balance'] ?? 0);
        }

        $html = view('suppliers/payables_statement_print', [
            'supplier' => $supplier,
            'start' => $start,
            'end' => $end,
            'asOfDate' => date('Y-m-d'),
            'dueDate' => $dueDate,
            'rows' => $rows,
            'totalPayables' => $totalPayables,
            'totalPayments' => $totalPayments,
            'endingBalance' => $endingBalance,
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
            ->setHeader('Content-Disposition', 'inline; filename="supplier-payables-statement.pdf"')
            ->setBody($dompdf->output());
    }

    private function supplierPayloadFromRequest(): array
    {
        $creditLimit = trim((string) $this->request->getPost('credit_limit'));
        $paymentTerm = trim((string) $this->request->getPost('payment_term'));

        return [
            'name' => trim((string) $this->request->getPost('name')),
            'address' => trim((string) $this->request->getPost('address')),
            'email' => trim((string) $this->request->getPost('email')),
            'phone' => trim((string) $this->request->getPost('phone')),
            'credit_limit' => $creditLimit === '' ? null : $creditLimit,
            'payment_term' => $paymentTerm === '' ? null : (int) $paymentTerm,
        ];
    }
}
