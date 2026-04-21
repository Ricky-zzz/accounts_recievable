<?php

namespace App\Controllers;

class Boa extends BaseController
{
    public function index(): string
    {
        $db = db_connect();

        $today = date('Y-m-d');
        $fromRaw = (string) $this->request->getGet('from');
        $toRaw = (string) $this->request->getGet('to');

        $from = $this->normalizeDate($fromRaw, $today);
        $to = $this->normalizeDate($toRaw, $today);

        if ($from > $to) {
            $swap = $from;
            $from = $to;
            $to = $swap;
        }

        if (! $db->tableExists('boa')) {
            return view('boa/index', [
                'from' => $from,
                'to' => $to,
                'records' => [],
                'bankColumns' => [],
                'tableMissing' => true,
            ]);
        }

        $columns = $db->getFieldNames('boa');
        $fixed = [
            'id',
            'date',
            'payor',
            'reference',
            'payment_id',
            'ar_trade',
            'created_at',
            'updated_at',
        ];
        $bankColumns = array_values(array_diff($columns, $fixed));

        $builder = $db->table('boa b');
        $builder->select('b.date, b.payor, b.reference, b.ar_trade');
        $builder->select('c.name as payor_name');

        foreach ($bankColumns as $column) {
            $builder->select('b.' . $column);
        }

        $builder->join('clients c', 'c.id = b.payor', 'left');
        $builder->where('b.date >=', $from);
        $builder->where('b.date <=', $to);
        $builder->orderBy('b.date', 'desc');
        $builder->orderBy('b.id', 'desc');

        return view('boa/index', [
            'from' => $from,
            'to' => $to,
            'records' => $builder->get()->getResultArray(),
            'bankColumns' => $bankColumns,
            'tableMissing' => false,
        ]);
    }

    private function normalizeDate(string $value, string $fallback): string
    {
        if ($value === '') {
            return $fallback;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $fallback;
        }

        return date('Y-m-d', $timestamp);
    }
}
