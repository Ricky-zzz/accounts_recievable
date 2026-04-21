<?php

namespace App\Controllers;

use App\Models\BankModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Banks extends BaseController
{
    public function index(): string
    {
        $model = new BankModel();

        return view('banks/index', [
            'banks' => $model->orderBy('bank_name', 'asc')->findAll(),
        ]);
    }

    public function createForm(): string
    {
        return view('banks/form', [
            'title' => 'New Bank',
            'action' => base_url('banks'),
            'bank' => [
                'bank_name' => '',
                'account_name' => '',
                'bank_number' => '',
            ],
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
            return view('banks/form', [
                'title' => 'New Bank',
                'action' => base_url('banks'),
                'bank' => $bank,
                'validation' => $this->validator,
            ]);
        }

        $model = new BankModel();
        $duplicate = $model->where('bank_name', $bank['bank_name'])->first();
        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'Bank name already exists.');
        }

        helper('boa');
        $boaColumn = boa_column_from_bank_name($bank['bank_name']);
        if ($boaColumn === '') {
            return redirect()->back()->withInput()->with('error', 'Bank name cannot be used as a BOA column.');
        }

        $db = db_connect();
        if (! $db->tableExists('boa')) {
            return redirect()->back()->withInput()->with('error', 'BOA table is missing. Run migrations first.');
        }

        if ($db->fieldExists($boaColumn, 'boa')) {
            return redirect()->back()->withInput()->with('error', 'BOA column already exists for this bank name.');
        }

        $bankId = $model->insert($bank, true);
        if (! $bankId) {
            return redirect()->back()->withInput()->with('error', 'Failed to create bank.');
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
            return redirect()->back()->withInput()->with('error', 'Failed to add BOA column for this bank.');
        }

        return redirect()->to('/banks')->with('success', 'Bank created.');
    }

    public function edit(int $id): string
    {
        $model = new BankModel();
        $bank = $model->find($id);

        if (! $bank) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('banks/form', [
            'title' => 'Edit Bank',
            'action' => base_url('banks/' . $id),
            'bank' => $bank,
        ]);
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
            return view('banks/form', [
                'title' => 'Edit Bank',
                'action' => base_url('banks/' . $id),
                'bank' => array_merge($existing, $bank),
                'validation' => $this->validator,
            ]);
        }

        $duplicate = $model->where('bank_name', $bank['bank_name'])->where('id !=', $id)->first();
        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'Bank name already exists.');
        }

        helper('boa');
        $oldBoaColumn = boa_column_from_bank_name($existing['bank_name']);
        $newBoaColumn = boa_column_from_bank_name($bank['bank_name']);

        if ($newBoaColumn === '') {
            return redirect()->back()->withInput()->with('error', 'Bank name cannot be used as a BOA column.');
        }

        if ($oldBoaColumn !== $newBoaColumn) {
            $db = db_connect();

            if (! $db->tableExists('boa')) {
                return redirect()->back()->withInput()->with('error', 'BOA table is missing. Run migrations first.');
            }

            if ($db->fieldExists($newBoaColumn, 'boa')) {
                return redirect()->back()->withInput()->with('error', 'BOA column already exists for the new bank name.');
            }

            if (! $db->fieldExists($oldBoaColumn, 'boa')) {
                return redirect()->back()->withInput()->with('error', 'Existing BOA column is missing for this bank.');
            }

            $hasUsage = $db->table('boa')->where($oldBoaColumn . ' >', 0)->countAllResults();
            if ($hasUsage > 0) {
                return redirect()->back()->withInput()->with('error', 'Cannot rename bank with BOA records.');
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
                return redirect()->back()->withInput()->with('error', 'Failed to update BOA column for this bank.');
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
