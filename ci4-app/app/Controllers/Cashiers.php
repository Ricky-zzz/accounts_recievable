<?php

namespace App\Controllers;

use App\Models\CashierReceiptRangeModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Cashiers extends BaseController
{
    private function redirectWithFormState(string $message, string $mode, ?int $id = null, array $errors = [])
    {
        $redirect = redirect()
            ->to('/cashiers')
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
        $userModel = new UserModel();
        $rangeModel = new CashierReceiptRangeModel();
        $query = trim((string) $this->request->getGet('q'));

        $builder = $userModel->orderBy('name', 'asc');

        if ($query !== '') {
            $builder
                ->groupStart()
                ->like('name', $query)
                ->orLike('username', $query)
                ->groupEnd();
        }

        $cashiers = $builder->paginate(15);
        $cashierIds = array_map(static fn (array $cashier): int => (int) ($cashier['id'] ?? 0), $cashiers);
        $ranges = [];

        if (! empty($cashierIds)) {
            $ranges = $rangeModel
                ->where('status', 'active')
                ->whereIn('user_id', $cashierIds)
                ->findAll();
        }

        $activeRanges = [];
        foreach ($ranges as $range) {
            $activeRanges[$range['user_id']] = $range;
        }

        return view('cashiers/index', [
            'cashiers' => $cashiers,
            'activeRanges' => $activeRanges,
            'query' => $query,
            'pager' => $userModel->pager,
        ]);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]',
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[6]',
        ];

        $name = trim((string) $this->request->getPost('name'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'create',
                null,
                $this->validator->getErrors()
            );
        }

        $userModel = new UserModel();

        $existingUsername = $userModel->where('username', $username)->first();
        if ($existingUsername) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'create',
                null,
                ['username' => 'This username is already in use.']
            );
        }

        $userModel->insert([
            'name' => $name,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'type' => 'cashier',
            'is_active' => 1,
        ]);

        return redirect()->to('/cashiers')->with('success', 'Cashier created.');
    }

    public function update(int $id)
    {
        $userModel = new UserModel();
        $cashier = $userModel->find($id);

        if (! $cashier) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($cashier['type'] ?? '') !== 'cashier') {
            return redirect()->to('/cashiers')->with('error', 'Only cashier users can be edited.');
        }

        $rules = [
            'name' => 'required|min_length[2]',
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'permit_empty|min_length[6]',
        ];

        $name = trim((string) $this->request->getPost('name'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'edit',
                $id,
                $this->validator->getErrors()
            );
        }

        $existingUsername = $userModel
            ->where('username', $username)
            ->where('id !=', $id)
            ->first();

        if ($existingUsername) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'edit',
                $id,
                ['username' => 'This username is already in use.']
            );
        }

        $data = [
            'name' => $name,
            'username' => $username,
        ];

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $userModel->update($id, $data);

        return redirect()->to('/cashiers')->with('success', 'Cashier updated.');
    }

    public function delete(int $id)
    {
        $userModel = new UserModel();
        $cashier = $userModel->find($id);

        if (! $cashier) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($cashier['type'] ?? '') !== 'cashier') {
            return redirect()->to('/cashiers')->with('error', 'Only cashier users can be deleted.');
        }

        $userModel->delete($id);

        return redirect()->to('/cashiers')->with('success', 'Cashier deleted.');
    }

    public function assignRange()
    {
        $userId = (int) $this->request->getPost('user_id');
        $startNo = (int) $this->request->getPost('start_no');
        $endNo = (int) $this->request->getPost('end_no');
        $sessionUserId = (int) (session('user_id') ?? 0);

        if ($userId <= 0 || $startNo <= 0 || $endNo <= 0 || $startNo > $endNo) {
            return redirect()->back()->with('error', 'Enter a valid receipt range.');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user) {
            return redirect()->to('/cashiers')->with('error', 'Selected user was not found.');
        }

        $userType = (string) ($user['type'] ?? '');
        $isCashier = $userType === 'cashier';
        $isCurrentAdmin = $userType === 'admin' && $userId === $sessionUserId;

        if (! $isCashier && ! $isCurrentAdmin) {
            return redirect()->to('/cashiers')->with('error', 'Receipt ranges can be assigned to cashiers, or to your own admin account only.');
        }

        $rangeModel = new CashierReceiptRangeModel();
        $active = $rangeModel
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if ($active) {
            $activeStart = max((int) $active['next_no'], (int) $active['start_no']);
            $activeEnd = (int) $active['end_no'];

            if ($activeStart > $activeEnd) {
                $rangeModel->update($active['id'], ['status' => 'closed', 'next_no' => $activeEnd + 1]);
            } else {
                $nextAvailable = $rangeModel->findNextAvailableNumber($userId, $activeStart, $activeEnd);
                if ($nextAvailable !== null) {
                    if ($nextAvailable !== (int) $active['next_no']) {
                        $rangeModel->update($active['id'], ['next_no' => $nextAvailable]);
                    }

                    return redirect()->back()->with('error', 'Active receipt range must be exhausted first.');
                }

                $rangeModel->update($active['id'], ['status' => 'closed', 'next_no' => $activeEnd + 1]);
            }
        }

        $nextAvailable = $rangeModel->findNextAvailableNumber($userId, $startNo, $endNo);
        if ($nextAvailable === null) {
            return redirect()->back()->with('error', 'All receipt numbers in this range are already used.');
        }

        $rangeModel->insert([
            'user_id' => $userId,
            'start_no' => $startNo,
            'end_no' => $endNo,
            'next_no' => $nextAvailable,
            'status' => 'active',
        ]);

        return redirect()->to('/cashiers')->with('success', 'Receipt range assigned.');
    }

    public function updateRange(int $id)
    {
        $startNo = (int) $this->request->getPost('start_no');
        $endNo = (int) $this->request->getPost('end_no');

        if ($startNo <= 0 || $endNo <= 0 || $startNo > $endNo) {
            return redirect()->back()->with('error', 'Enter a valid receipt range.');
        }

        $rangeModel = new CashierReceiptRangeModel();
        $range = $rangeModel->find($id);

        if (! $range) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($range['status'] ?? '') !== 'active') {
            return redirect()->to('/cashiers')->with('error', 'Only active receipt ranges can be edited.');
        }

        $userModel = new UserModel();
        $user = $userModel->find((int) $range['user_id']);

        if (! $user) {
            return redirect()->to('/cashiers')->with('error', 'Selected user was not found.');
        }

        $sessionUserId = (int) (session('user_id') ?? 0);
        $userType = (string) ($user['type'] ?? '');
        $isCashier = $userType === 'cashier';
        $isCurrentAdmin = $userType === 'admin' && (int) $user['id'] === $sessionUserId;

        if (! $isCashier && ! $isCurrentAdmin) {
            return redirect()->to('/cashiers')->with('error', 'Receipt ranges can be edited for cashiers, or for your own admin account only.');
        }

        $nextAvailable = $rangeModel->findNextAvailableNumber((int) $range['user_id'], $startNo, $endNo);
        if ($nextAvailable === null) {
            return redirect()->back()->with('error', 'All receipt numbers in this range are already used.');
        }

        $rangeModel->update($id, [
            'start_no' => $startNo,
            'end_no' => $endNo,
            'next_no' => $nextAvailable,
            'status' => 'active',
        ]);

        return redirect()->to('/cashiers')->with('success', 'Receipt range updated.');
    }

    public function clearRange(int $id)
    {
        $rangeModel = new CashierReceiptRangeModel();
        $range = $rangeModel->find($id);

        if (! $range) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($range['status'] ?? '') !== 'active') {
            return redirect()->to('/cashiers')->with('error', 'Only active receipt ranges can be cleared.');
        }

        $userModel = new UserModel();
        $user = $userModel->find((int) $range['user_id']);

        if (! $user) {
            return redirect()->to('/cashiers')->with('error', 'Selected user was not found.');
        }

        $sessionUserId = (int) (session('user_id') ?? 0);
        $userType = (string) ($user['type'] ?? '');
        $isCashier = $userType === 'cashier';
        $isCurrentAdmin = $userType === 'admin' && (int) $user['id'] === $sessionUserId;

        if (! $isCashier && ! $isCurrentAdmin) {
            return redirect()->to('/cashiers')->with('error', 'Receipt ranges can be cleared for cashiers, or for your own admin account only.');
        }

        $rangeModel->update($id, [
            'status' => 'closed',
            'next_no' => (int) $range['end_no'] + 1,
        ]);

        return redirect()->to('/cashiers')->with('success', 'Receipt range cleared.');
    }
}
