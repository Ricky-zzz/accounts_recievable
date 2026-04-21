<?php

namespace App\Controllers;

use App\Models\ClientModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Clients extends BaseController
{
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

    public function createForm(): string
    {
        return view('clients/form', [
            'title' => 'New Client',
            'action' => base_url('clients'),
            'client' => [
                'name' => '',
                'address' => '',
                'email' => '',
                'phone' => '',
            ],
        ]);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]',
            'email' => 'permit_empty|valid_email',
            'phone' => 'permit_empty|max_length[50]',
        ];

        $client = [
            'name' => trim((string) $this->request->getPost('name')),
            'address' => trim((string) $this->request->getPost('address')),
            'email' => trim((string) $this->request->getPost('email')),
            'phone' => trim((string) $this->request->getPost('phone')),
        ];

        if (! $this->validate($rules)) {
            return view('clients/form', [
                'title' => 'New Client',
                'action' => base_url('clients'),
                'client' => $client,
                'validation' => $this->validator,
            ]);
        }

        $model = new ClientModel();
        $model->insert($client);

        return redirect()->to('/clients')->with('success', 'Client created.');
    }

    public function edit(int $id): string
    {
        $model = new ClientModel();
        $client = $model->find($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('clients/form', [
            'title' => 'Edit Client',
            'action' => base_url('clients/' . $id),
            'client' => $client,
        ]);
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
        ];

        $client = [
            'name' => trim((string) $this->request->getPost('name')),
            'address' => trim((string) $this->request->getPost('address')),
            'email' => trim((string) $this->request->getPost('email')),
            'phone' => trim((string) $this->request->getPost('phone')),
        ];

        if (! $this->validate($rules)) {
            return view('clients/form', [
                'title' => 'Edit Client',
                'action' => base_url('clients/' . $id),
                'client' => array_merge($existing, $client),
                'validation' => $this->validator,
            ]);
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
