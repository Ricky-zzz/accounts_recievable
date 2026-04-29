<?php

namespace App\Controllers;

use App\Models\SupplierModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Suppliers extends BaseController
{
    private function redirectWithFormState(string $message, string $mode, ?int $id = null)
    {
        $redirect = redirect()
            ->to('/suppliers')
            ->withInput()
            ->with('error', $message)
            ->with('form_mode', $mode);

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
            return $this->redirectWithFormState('Please correct the highlighted fields.', 'create');
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
            return $this->redirectWithFormState('Please correct the highlighted fields.', 'edit', $id);
        }

        $model->update($id, $this->supplierPayloadFromRequest());

        return redirect()->to('/suppliers')->with('success', 'Supplier updated.');
    }

    public function delete(int $id)
    {
        (new SupplierModel())->delete($id);

        return redirect()->to('/suppliers')->with('success', 'Supplier deleted.');
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
