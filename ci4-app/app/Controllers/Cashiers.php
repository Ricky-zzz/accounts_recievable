<?php

namespace App\Controllers;

use App\Models\CashierModel;
use App\Models\CashierReceiptRangeModel;

class Cashiers extends BaseController
{
    public function index(): string
    {
        $cashierModel = new CashierModel();
        $rangeModel = new CashierReceiptRangeModel();

        $cashiers = $cashierModel->orderBy('name', 'asc')->findAll();
        $ranges = $rangeModel->where('status', 'active')->findAll();

        $activeRanges = [];
        foreach ($ranges as $range) {
            $activeRanges[$range['cashier_id']] = $range;
        }

        return view('cashiers/index', [
            'cashiers' => $cashiers,
            'activeRanges' => $activeRanges,
        ]);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]',
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please complete the cashier form.');
        }

        $cashierModel = new CashierModel();

        $cashierModel->insert([
            'name' => trim((string) $this->request->getPost('name')),
            'username' => trim((string) $this->request->getPost('username')),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'is_active' => 1,
        ]);

        return redirect()->to('/cashiers')->with('success', 'Cashier created.');
    }

    public function assignRange()
    {
        $cashierId = (int) $this->request->getPost('cashier_id');
        $startNo = (int) $this->request->getPost('start_no');
        $endNo = (int) $this->request->getPost('end_no');

        if ($cashierId <= 0 || $startNo <= 0 || $endNo <= 0 || $startNo > $endNo) {
            return redirect()->back()->with('error', 'Enter a valid receipt range.');
        }

        $rangeModel = new CashierReceiptRangeModel();
        $active = $rangeModel
            ->where('cashier_id', $cashierId)
            ->where('status', 'active')
            ->first();

        if ($active && (int) $active['next_no'] <= (int) $active['end_no']) {
            return redirect()->back()->with('error', 'Active receipt range must be exhausted first.');
        }

        if ($active) {
            $rangeModel->update($active['id'], ['status' => 'closed']);
        }

        $rangeModel->insert([
            'cashier_id' => $cashierId,
            'start_no' => $startNo,
            'end_no' => $endNo,
            'next_no' => $startNo,
            'status' => 'active',
        ]);

        return redirect()->to('/cashiers')->with('success', 'Receipt range assigned.');
    }
}
