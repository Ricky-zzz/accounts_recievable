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
            ->with('form_mode', $mode)
            ->with('form_errors', $errors);

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

        $cashiers = $builder->findAll();
        $ranges = $rangeModel->where('status', 'active')->findAll();

        $activeRanges = [];
        foreach ($ranges as $range) {
            $activeRanges[$range['user_id']] = $range;
        }

        return view('cashiers/index', [
            'cashiers' => $cashiers,
            'activeRanges' => $activeRanges,
            'query' => $query,
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
