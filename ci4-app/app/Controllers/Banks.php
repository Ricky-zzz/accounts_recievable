<?php

namespace App\Controllers;

use App\Models\BankModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Banks extends BaseController
{
    private function redirectWithFormState(string $message, string $mode, ?int $id = null, array $errors = [])
    {
        $redirect = redirect()
            ->to('/banks')
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
        $model = new BankModel();

        return view('banks/index', [
            'banks' => $model->orderBy('bank_name', 'asc')->findAll(),
        ]);
    }

    public function create()
    {
        $rules = [
            'bank_name' => 'required|max_length[150]',
            'account_name' => 'permit_empty|max_length[150]',
            'bank_number' => 'permit_empty|max_length[50]',
        ];

        $bank = [
            'bank_name' => trim((string) $this->request->getPost('bank_name')),
            'account_name' => trim((string) $this->request->getPost('account_name')),
            'bank_number' => trim((string) $this->request->getPost('bank_number')),
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'create',
                null,
                $this->validator->getErrors()
            );
        }

        $model = new BankModel();
        $duplicate = $model->where('bank_name', $bank['bank_name'])->first();
        if ($duplicate) {
            return $this->redirectWithFormState('Bank name already exists.', 'create', null, [
                'bank_name' => 'Bank name already exists.',
            ]);
        }

        helper('boa');
        $boaColumn = boa_column_from_bank_name($bank['bank_name']);
        if ($boaColumn === '') {
            return $this->redirectWithFormState('Bank name cannot be used as a BOA column.', 'create', null, [
                'bank_name' => 'Bank name cannot be used as a BOA column.',
            ]);
        }

        $db = db_connect();
        if (! $db->tableExists('boa')) {
            return $this->redirectWithFormState('BOA table is missing. Run migrations first.', 'create');
        }

        if ($db->fieldExists($boaColumn, 'boa')) {
            return $this->redirectWithFormState('BOA column already exists for this bank name.', 'create', null, [
                'bank_name' => 'BOA column already exists for this bank name.',
            ]);
        }

        $bankId = $model->insert($bank, true);
        if (! $bankId) {
            return $this->redirectWithFormState('Failed to create bank.', 'create');
        }

        $forge = \Config\Database::forge($db);
        try {
            $forge->addColumn('boa', [
                $boaColumn => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0,
                    'after'      => 'payment_id',
                ],
            ]);
        } catch (\Throwable $e) {
            $model->delete($bankId);
            return $this->redirectWithFormState('Failed to add BOA column for this bank.', 'create');
        }

        return redirect()->to('/banks')->with('success', 'Bank created.');
    }

    public function update(int $id)
    {
        $model = new BankModel();
        $existing = $model->find($id);

        if (! $existing) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'bank_name' => 'required|max_length[150]',
            'account_name' => 'permit_empty|max_length[150]',
            'bank_number' => 'permit_empty|max_length[50]',
        ];

        $bank = [
            'bank_name' => trim((string) $this->request->getPost('bank_name')),
            'account_name' => trim((string) $this->request->getPost('account_name')),
            'bank_number' => trim((string) $this->request->getPost('bank_number')),
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'edit',
                $id,
                $this->validator->getErrors()
            );
        }

        $duplicate = $model->where('bank_name', $bank['bank_name'])->where('id !=', $id)->first();
        if ($duplicate) {
            return $this->redirectWithFormState('Bank name already exists.', 'edit', $id, [
                'bank_name' => 'Bank name already exists.',
            ]);
        }

        helper('boa');
        $oldBoaColumn = boa_column_from_bank_name($existing['bank_name']);
        $newBoaColumn = boa_column_from_bank_name($bank['bank_name']);

        if ($newBoaColumn === '') {
            return $this->redirectWithFormState('Bank name cannot be used as a BOA column.', 'edit', $id, [
                'bank_name' => 'Bank name cannot be used as a BOA column.',
            ]);
        }

        if ($oldBoaColumn !== $newBoaColumn) {
            $db = db_connect();

            if (! $db->tableExists('boa')) {
                return $this->redirectWithFormState('BOA table is missing. Run migrations first.', 'edit', $id);
            }

            if ($db->fieldExists($newBoaColumn, 'boa')) {
                return $this->redirectWithFormState('BOA column already exists for the new bank name.', 'edit', $id, [
                    'bank_name' => 'BOA column already exists for the new bank name.',
                ]);
            }

            if (! $db->fieldExists($oldBoaColumn, 'boa')) {
                return $this->redirectWithFormState('Existing BOA column is missing for this bank.', 'edit', $id);
            }

            $hasUsage = $db->table('boa')->where($oldBoaColumn . ' >', 0)->countAllResults();
            if ($hasUsage > 0) {
                return $this->redirectWithFormState('Cannot rename bank with BOA records.', 'edit', $id, [
                    'bank_name' => 'Cannot rename bank with BOA records.',
                ]);
            }

            $forge = \Config\Database::forge($db);
            try {
                $forge->dropColumn('boa', $oldBoaColumn);
                $forge->addColumn('boa', [
                    $newBoaColumn => [
                        'type'       => 'DECIMAL',
                        'constraint' => '12,2',
                        'default'    => 0,
                        'after'      => 'payment_id',
                    ],
                ]);
            } catch (\Throwable $e) {
                return $this->redirectWithFormState('Failed to update BOA column for this bank.', 'edit', $id);
            }
        }

        $model->update($id, $bank);

        return redirect()->to('/banks')->with('success', 'Bank updated.');
    }

    public function delete(int $id)
    {
        $model = new BankModel();
        $bank = $model->find($id);

        if (! $bank) {
            throw PageNotFoundException::forPageNotFound();
        }

        helper('boa');
        $boaColumn = boa_column_from_bank_name($bank['bank_name']);
        if ($boaColumn === '') {
            return redirect()->back()->with('error', 'Bank name cannot be used as a BOA column.');
        }

        $db = db_connect();
        if ($db->tableExists('payments')) {
            $paymentCount = $db->table('payments')->where('deposit_bank_id', $id)->countAllResults();
            if ($paymentCount > 0) {
                return redirect()->back()->with('error', 'Cannot delete bank with existing payments.');
            }
        }

        if ($db->tableExists('boa') && $db->fieldExists($boaColumn, 'boa')) {
            $hasUsage = $db->table('boa')->where($boaColumn . ' >', 0)->countAllResults();
            if ($hasUsage > 0) {
                return redirect()->back()->with('error', 'Cannot delete bank with BOA records.');
            }

            $forge = \Config\Database::forge($db);
            try {
                $forge->dropColumn('boa', $boaColumn);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Failed to remove BOA column for this bank.');
            }
        }

        $model->delete($id);

        return redirect()->to('/banks')->with('success', 'Bank deleted.');
    }
}
