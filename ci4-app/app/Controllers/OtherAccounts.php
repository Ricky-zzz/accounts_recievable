<?php

namespace App\Controllers;

use App\Models\OtherAccountModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class OtherAccounts extends BaseController
{
    public function index(): string
    {
        $model = new OtherAccountModel();

        $query = trim((string) $this->request->getGet('q'));
        $builder = $model->orderBy('account_code', 'asc');

        if ($query !== '') {
            $builder
                ->groupStart()
                ->like('account_code', $query)
                ->orLike('name', $query)
                ->groupEnd();
        }

        return view('other_accounts/index', [
            'accounts' => $builder->findAll(),
            'query' => $query,
        ]);
    }

    public function create()
    {
        $rules = [
            'account_code' => 'required|min_length[1]|max_length[50]',
            'name' => 'required|min_length[2]|max_length[100]',
            'type' => 'required|in_list[dr,cr]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(422);
        }

        $account = [
            'account_code' => trim((string) $this->request->getPost('account_code')),
            'name' => trim((string) $this->request->getPost('name')),
            'type' => trim((string) $this->request->getPost('type')),
        ];

        $model = new OtherAccountModel();
        $id = $model->insert($account, true);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Account created successfully',
            'id' => $id,
        ]);
    }

    public function update(int $id)
    {
        $model = new OtherAccountModel();
        $existing = $model->find($id);

        if (! $existing) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Account not found',
            ])->setStatusCode(404);
        }

        $rules = [
            'account_code' => 'required|min_length[1]|max_length[50]',
            'name' => 'required|min_length[2]|max_length[100]',
            'type' => 'required|in_list[dr,cr]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(422);
        }

        $account = [
            'account_code' => trim((string) $this->request->getPost('account_code')),
            'name' => trim((string) $this->request->getPost('name')),
            'type' => trim((string) $this->request->getPost('type')),
        ];

        $model->update($id, $account);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Account updated successfully',
        ]);
    }

    public function delete(int $id)
    {
        $model = new OtherAccountModel();
        $existing = $model->find($id);

        if (! $existing) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Account not found',
            ])->setStatusCode(404);
        }

        $model->delete($id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    public function getAccount(int $id)
    {
        $model = new OtherAccountModel();
        $account = $model->find($id);

        if (! $account) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Account not found',
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $account,
        ]);
    }
}
