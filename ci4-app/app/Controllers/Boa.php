<?php

namespace App\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;

class Boa extends BaseController
{
    public function index(): string
    {
        $today = date('Y-m-d');
        $fromRaw = (string) $this->request->getGet('from');
        $toRaw = (string) $this->request->getGet('to');

        $from = $this->normalizeDate($fromRaw, $today);
        $to = $this->normalizeDate($toRaw, $today);

        $data = $this->buildReportData($from, $to);
        return view('boa/index', $data);
    }

    public function print()
    {
        $today = date('Y-m-d');
        $fromRaw = (string) $this->request->getGet('from');
        $toRaw = (string) $this->request->getGet('to');

        $from = $this->normalizeDate($fromRaw, $today);
        $to = $this->normalizeDate($toRaw, $today);

        $data = $this->buildReportData($from, $to);
        $html = view('boa/print', $data);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="boa-report.pdf"')
            ->setBody($dompdf->output());
    }

    private function buildReportData(string $from, string $to): array
    {
        if ($from > $to) {
            $swap = $from;
            $from = $to;
            $to = $swap;
        }

        $db = db_connect();

        if (! $db->tableExists('boa')) {
            return [
                'from' => $from,
                'to' => $to,
                'records' => [],
                'bankColumns' => [],
                'tableMissing' => true,
            ];
        }

        $columns = $db->getFieldNames('boa');
        $fixed = [
            'id',
            'date',
            'payor',
            'reference',
            'payment_id',
            'ar_trade',
            'ar_others',
            'account_title',
            'dr',
            'cr',
            'note',
            'description',
            'created_at',
            'updated_at',
        ];
        $bankColumns = array_values(array_diff($columns, $fixed));

        $builder = $db->table('boa b');
        $builder->select('b.date, b.payor, b.reference, b.ar_trade, b.ar_others, b.account_title, b.dr, b.cr, b.note, b.description');
        $builder->select('c.name as payor_name');

        foreach ($bankColumns as $column) {
            $builder->select('b.' . $column);
        }

        $builder->join('clients c', 'c.id = b.payor', 'left');
        $builder->where('b.date >=', $from);
        $builder->where('b.date <=', $to);
        $builder->orderBy('b.date', 'desc');
        $builder->orderBy('b.id', 'asc');

        $records = $builder->get()->getResultArray();
        $totals = [
            'bankColumns' => array_fill_keys($bankColumns, 0.0),
            'ar_trade' => 0.0,
            'ar_others' => 0.0,
            'dr' => 0.0,
            'cr' => 0.0,
        ];

        foreach ($records as $row) {
            foreach ($bankColumns as $column) {
                $totals['bankColumns'][$column] += (float) ($row[$column] ?? 0);
            }

            $totals['ar_trade'] += (float) ($row['ar_trade'] ?? 0);
            $totals['ar_others'] += (float) ($row['ar_others'] ?? 0);
            $totals['dr'] += (float) ($row['dr'] ?? 0);
            $totals['cr'] += (float) ($row['cr'] ?? 0);
        }

        return [
            'from' => $from,
            'to' => $to,
            'records' => $records,
            'bankColumns' => $bankColumns,
            'totals' => $totals,
            'tableMissing' => false,
        ];
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
