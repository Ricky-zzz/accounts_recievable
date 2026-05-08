<?php

namespace App\Controllers;

use App\Models\BankModel;
use App\Models\UserModel;
use App\Services\PayablePostingService;
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
            ->select('suppliers.payment_term as supplier_payment_term')
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
                'supplier_id' => (int) ($supplierOrder['supplier_id'] ?? 0),
                'supplier_name' => (string) ($supplierOrder['supplier_name'] ?? ''),
                'po_no' => (string) ($supplierOrder['po_no'] ?? ''),
                'rr_no' => (string) ($consumption['rr_no'] ?? ''),
                'qty' => $qty,
                'po_balance' => $runningBalance,
                'total_amount' => (float) ($consumption['total_amount'] ?? 0),
                'allocated_amount' => (float) ($consumption['allocated_amount'] ?? 0),
                'balance' => (float) ($consumption['balance'] ?? 0),
            ];
        }

        return [
            'supplierOrder' => $supplierOrder,
            'pickupFormData' => $this->buildPickupFormData($supplierOrderId),
            'quickPayData' => $this->buildQuickPayData(),
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
            ->select('po.total_amount')
            ->select('COALESCE(payables_summary.allocated_amount, 0) as allocated_amount')
            ->select('(po.total_amount - COALESCE(payables_summary.allocated_amount, 0)) as balance')
            ->select('SUM(poi.qty) as qty')
            ->join('purchase_orders po', 'po.id = poi.purchase_order_id', 'left')
            ->join('supplier_order_items soi', 'soi.id = poi.supplier_order_item_id', 'left')
            ->join(
                "(SELECT pa.purchase_order_id, SUM(pa.amount) as allocated_amount FROM payable_allocations pa JOIN payables p ON p.id = pa.payable_id WHERE p.status = 'posted' GROUP BY pa.purchase_order_id) payables_summary",
                'payables_summary.purchase_order_id = po.id',
                'left'
            )
            ->where('soi.supplier_order_id', $supplierOrderId)
            ->where('po.voided_at', null)
            ->groupBy('po.id, po.po_no, po.date, po.total_amount, payables_summary.allocated_amount')
            ->orderBy('po.date', 'asc')
            ->orderBy('po.id', 'asc')
            ->get()
            ->getResultArray();
    }

    private function buildQuickPayData(): array
    {
        $userId = (int) (session('user_id') ?? 0);
        $assignedUser = $userId > 0 ? (new UserModel())->find($userId) : null;
        $activeRange = (new PayablePostingService())->getActiveReceiptRange($userId);

        return [
            'assignedUser' => $assignedUser,
            'activeReceipt' => $activeRange ? (int) $activeRange['next_no'] : null,
            'rangeEnd' => $activeRange ? (int) $activeRange['end_no'] : null,
            'banks' => (new BankModel())->orderBy('bank_name', 'asc')->findAll(),
        ];
    }

    private function buildPickupFormData(int $supplierOrderId): array
    {
        $items = db_connect()->table('supplier_order_items soi')
            ->select('soi.id, soi.supplier_order_id, soi.product_id, soi.qty_ordered, soi.qty_picked_up, soi.qty_balance')
            ->select('products.product_name, products.unit_price')
            ->join('products', 'products.id = soi.product_id', 'left')
            ->where('soi.supplier_order_id', $supplierOrderId)
            ->where('soi.qty_balance >', 0)
            ->orderBy('soi.id', 'asc')
            ->get()
            ->getResultArray();

        return [
            'items' => $items,
        ];
    }

    private function resolveDateRange(): array
    {
        return [
            trim((string) ($this->request->getGet('from_date') ?? '')),
            trim((string) ($this->request->getGet('to_date') ?? '')),
        ];
    }
}
