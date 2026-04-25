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

        $clients = $builder->paginate(15);

        $clientIds = array_map(static fn (array $client): int => (int) $client['id'], $clients);
        $balancesByClient = [];
        $db = db_connect();

        if (! empty($clientIds)) {
            $ledgerRows = $db->table('ledger l')
                ->select('l.client_id, l.balance')
                ->whereIn('l.client_id', $clientIds)
                ->orderBy('l.client_id', 'asc')
                ->orderBy('l.entry_date', 'desc')
                ->orderBy('l.id', 'desc')
                ->get()
                ->getResultArray();

            foreach ($ledgerRows as $row) {
                $clientId = (int) ($row['client_id'] ?? 0);
                if ($clientId > 0 && ! array_key_exists($clientId, $balancesByClient)) {
                    $balancesByClient[$clientId] = (float) ($row['balance'] ?? 0);
                }
            }
        }

        foreach ($clients as $index => $client) {
            $clientId = (int) ($client['id'] ?? 0);
            $creditLimit = isset($client['credit_limit']) ? (float) $client['credit_limit'] : 0.0;
            $currentBalance = $balancesByClient[$clientId] ?? 0.0;

            $clients[$index]['current_balance'] = $currentBalance;
            $clients[$index]['available_credit'] = $creditLimit - $currentBalance;
        }

        return view('clients/index', [
            'clients' => $clients,
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
