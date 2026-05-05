<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class SupplierOrderLedger extends BaseController
{
    public function show(int $supplierOrderId): string
    {
        [$fromDate, $toDate] = $this->resolveDateRange();

        return view('supplier_order_ledger/index', $this->buildLedgerData($supplierOrderId, $fromDate, $toDate));
    }

    public function print(int $supplierOrderId)
    {
        [$fromDate, $toDate] = $this->resolveDateRange();
        $html = view('supplier_order_ledger/print', $this->buildLedgerData($supplierOrderId, $fromDate, $toDate));

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="supplier-po-ledger.pdf"')
            ->setBody($dompdf->output());
    }

    private function buildLedgerData(int $supplierOrderId, string $fromDate, string $toDate): array
    {
        $db = db_connect();
        $supplierOrder = $db->table('supplier_orders so')
            ->select('so.*, suppliers.name as supplier_name')
            ->join('suppliers', 'suppliers.id = so.supplier_id', 'left')
            ->where('so.id', $supplierOrderId)
            ->get()
            ->getRowArray();

        if (! $supplierOrder) {
            throw PageNotFoundException::forPageNotFound();
        }

        $orderedTotal = (float) (($db->table('supplier_order_items')
            ->select('COALESCE(SUM(qty_ordered), 0) as ordered_total')
            ->where('supplier_order_id', $supplierOrderId)
            ->get()
            ->getRowArray()['ordered_total'] ?? 0));

        $allConsumptions = $this->fetchConsumptions($supplierOrderId);
        $openingBalance = $orderedTotal;
        foreach ($allConsumptions as $consumption) {
            if ($fromDate !== '' && (string) $consumption['date'] < $fromDate) {
                $openingBalance -= (float) ($consumption['qty'] ?? 0);
            }
        }

        $runningBalance = $fromDate === '' ? $orderedTotal : $openingBalance;
        $rows = [[
            'type' => 'opening',
            'date' => $fromDate === '' ? (string) ($supplierOrder['date'] ?? '') : 'Before ' . $fromDate,
            'supplier_order_id' => $supplierOrderId,
            'purchase_order_id' => null,
            'po_no' => (string) ($supplierOrder['po_no'] ?? ''),
            'rr_no' => '',
            'qty' => null,
            'po_balance' => $runningBalance,
        ]];

        foreach ($allConsumptions as $consumption) {
            $date = (string) ($consumption['date'] ?? '');
            if ($fromDate !== '' && $date < $fromDate) {
                continue;
            }

            if ($toDate !== '' && $date > $toDate) {
                continue;
            }

            $qty = (float) ($consumption['qty'] ?? 0);
            $runningBalance -= $qty;
            $rows[] = [
                'type' => 'movement',
                'date' => $date,
                'supplier_order_id' => $supplierOrderId,
                'purchase_order_id' => (int) ($consumption['purchase_order_id'] ?? 0),
                'po_no' => (string) ($supplierOrder['po_no'] ?? ''),
                'rr_no' => (string) ($consumption['rr_no'] ?? ''),
                'qty' => $qty,
                'po_balance' => $runningBalance,
            ];
        }

        return [
            'supplierOrder' => $supplierOrder,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'openingBalance' => $openingBalance,
            'endingBalance' => $runningBalance,
            'orderedTotal' => $orderedTotal,
            'rows' => $rows,
        ];
    }

    private function fetchConsumptions(int $supplierOrderId): array
    {
        return db_connect()->table('purchase_order_items poi')
            ->select('po.id as purchase_order_id, po.po_no as rr_no, po.date')
            ->select('SUM(poi.qty) as qty')
            ->join('purchase_orders po', 'po.id = poi.purchase_order_id', 'left')
            ->join('supplier_order_items soi', 'soi.id = poi.supplier_order_item_id', 'left')
            ->where('soi.supplier_order_id', $supplierOrderId)
            ->where('po.voided_at', null)
            ->groupBy('po.id, po.po_no, po.date')
            ->orderBy('po.date', 'asc')
            ->orderBy('po.id', 'asc')
            ->get()
            ->getResultArray();
    }

    private function resolveDateRange(): array
    {
        return [
            trim((string) ($this->request->getGet('from_date') ?? '')),
            trim((string) ($this->request->getGet('to_date') ?? '')),
        ];
    }
}
