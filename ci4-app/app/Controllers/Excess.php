<?php

namespace App\Controllers;

class Excess extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $query = trim((string) $this->request->getGet('q'));

        $builder = $db->table('payments p')
            ->select('p.pr_no, p.date, p.amount_received, p.amount_allocated')
            ->select('(p.amount_received - p.amount_allocated) as excess')
            ->select('c.name as client_name')
            ->join('clients c', 'c.id = p.client_id', 'left')
            ->where('p.status', 'posted');

        if ($query !== '') {
            $builder->like('c.name', $query);
        }

        $rows = $builder
            ->orderBy('p.date', 'desc')
            ->orderBy('p.id', 'desc')
            ->get()
            ->getResultArray();

        return view('excess/index', [
            'rows' => $rows,
            'query' => $query,
        ]);
    }
}
