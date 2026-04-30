<?php

namespace App\Controllers;

use App\Models\ClientModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

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

    public function soaPrint(int $id)
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $start = trim((string) ($this->request->getGet('start') ?? ''));
        $end = trim((string) ($this->request->getGet('end') ?? ''));
        $dueDate = trim((string) ($this->request->getGet('due_date') ?? ''));

        if ($start === '') {
            $start = date('Y-m-01');
        }

        if ($end === '') {
            $end = date('Y-m-t');
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $db = db_connect();
        $postedAllocations = $db->table('payment_allocations pa')
            ->select('pa.delivery_id, SUM(pa.amount) as allocated_amount')
            ->join('payments p', 'p.id = pa.payment_id', 'inner')
            ->where('p.status', 'posted')
            ->groupBy('pa.delivery_id')
            ->getCompiledSelect();

        $rows = $db->table('deliveries d')
            ->select('d.date as entry_date, d.dr_no, d.due_date, d.total_amount as amount')
            ->select('COALESCE(payments_summary.allocated_amount, 0) as collection')
            ->select('(d.total_amount - COALESCE(payments_summary.allocated_amount, 0)) as balance')
            ->join("({$postedAllocations}) payments_summary", 'payments_summary.delivery_id = d.id', 'left')
            ->where('d.client_id', $id)
            ->where('d.status', 'active')
            ->where('d.voided_at', null)
            ->where('d.date <=', date('Y-m-d'))
            ->having('balance >', 0)
            ->orderBy('d.date', 'asc')
            ->orderBy('d.id', 'asc')
            ->get()
            ->getResultArray();

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $endingBalance = 0.0;

        foreach ($rows as $row) {
            $totalDebit += (float) ($row['amount'] ?? 0);
            $totalCredit += (float) ($row['collection'] ?? 0);
            $endingBalance += (float) ($row['balance'] ?? 0);
        }

        $html = view('clients/soa_print', [
            'client' => $client,
            'start' => $start,
            'end' => $end,
            'asOfDate' => date('Y-m-d'),
            'dueDate' => $dueDate,
            'openingBalance' => 0,
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'endingBalance' => $endingBalance,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="statement-of-account.pdf"')
            ->setBody($dompdf->output());
    }
}
