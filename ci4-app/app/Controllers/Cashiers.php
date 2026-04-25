<?php

namespace App\Controllers;

use App\Models\CashierReceiptRangeModel;
use App\Models\UserModel;

class Cashiers extends BaseController
{
    public function index(): string
    {
        $userModel = new UserModel();
        $rangeModel = new CashierReceiptRangeModel();

        $cashiers = $userModel->orderBy('name', 'asc')->findAll();
        $ranges = $rangeModel->where('status', 'active')->findAll();

        $activeRanges = [];
        foreach ($ranges as $range) {
            $activeRanges[$range['user_id']] = $range;
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

        $userModel = new UserModel();

        $type = trim((string) $this->request->getPost('type'));
        if (! in_array($type, ['cashier', 'admin'], true)) {
            $type = 'cashier';
        }

        $userModel->insert([
            'name' => trim((string) $this->request->getPost('name')),
            'username' => trim((string) $this->request->getPost('username')),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'type' => $type,
            'is_active' => 1,
        ]);

        return redirect()->to('/cashiers')->with('success', 'User created.');
    }

    public function assignRange()
    {
        $userId = (int) $this->request->getPost('user_id');
        $startNo = (int) $this->request->getPost('start_no');
        $endNo = (int) $this->request->getPost('end_no');

        if ($userId <= 0 || $startNo <= 0 || $endNo <= 0 || $startNo > $endNo) {
            return redirect()->back()->with('error', 'Enter a valid receipt range.');
        }

        $rangeModel = new CashierReceiptRangeModel();
        $active = $rangeModel
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if ($active && (int) $active['next_no'] <= (int) $active['end_no']) {
            return redirect()->back()->with('error', 'Active receipt range must be exhausted first.');
        }

        if ($active) {
            $rangeModel->update($active['id'], ['status' => 'closed']);
        }

        $rangeModel->insert([
            'user_id' => $userId,
            'start_no' => $startNo,
            'end_no' => $endNo,
            'next_no' => $startNo,
            'status' => 'active',
        ]);

        return redirect()->to('/cashiers')->with('success', 'Receipt range assigned.');
    }
}
