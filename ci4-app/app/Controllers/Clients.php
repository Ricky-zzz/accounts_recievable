<?php

namespace App\Controllers;

use App\Models\ClientModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Clients extends BaseController
{
    private function redirectWithFormState(string $message, string $mode, ?int $id = null, array $errors = [])
    {
        $redirect = redirect()
            ->to('/clients')
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
        $model = new ClientModel();

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

        return view('clients/index', [
            'clients' => $builder->findAll(),
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

        $creditLimit = trim((string) $this->request->getPost('credit_limit'));
        $paymentTerm = trim((string) $this->request->getPost('payment_term'));

        $client = [
            'name' => trim((string) $this->request->getPost('name')),
            'address' => trim((string) $this->request->getPost('address')),
            'email' => trim((string) $this->request->getPost('email')),
            'phone' => trim((string) $this->request->getPost('phone')),
            'credit_limit' => $creditLimit === '' ? null : $creditLimit,
            'payment_term' => $paymentTerm === '' ? null : (int) $paymentTerm,
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'create',
                null,
                $this->validator->getErrors()
            );
        }

        $model = new ClientModel();
        $model->insert($client);

        return redirect()->to('/clients')->with('success', 'Client created.');
    }

    public function update(int $id)
    {
        $model = new ClientModel();
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

        $creditLimit = trim((string) $this->request->getPost('credit_limit'));
        $paymentTerm = trim((string) $this->request->getPost('payment_term'));

        $client = [
            'name' => trim((string) $this->request->getPost('name')),
            'address' => trim((string) $this->request->getPost('address')),
            'email' => trim((string) $this->request->getPost('email')),
            'phone' => trim((string) $this->request->getPost('phone')),
            'credit_limit' => $creditLimit === '' ? null : $creditLimit,
            'payment_term' => $paymentTerm === '' ? null : (int) $paymentTerm,
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'edit',
                $id,
                $this->validator->getErrors()
            );
        }

        $model->update($id, $client);

        return redirect()->to('/clients')->with('success', 'Client updated.');
    }

    public function delete(int $id)
    {
        $model = new ClientModel();
        $model->delete($id);

        return redirect()->to('/clients')->with('success', 'Client deleted.');
    }
}
