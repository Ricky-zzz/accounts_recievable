<?php

namespace App\Controllers;

use App\Services\TransactionDetailService;

class TransactionDetails extends BaseController
{
    public function delivery(int $deliveryId)
    {
        return $this->jsonDetail((new TransactionDetailService())->delivery($deliveryId), 'Delivery not found.');
    }

    public function payment(int $paymentId)
    {
        return $this->jsonDetail((new TransactionDetailService())->payment($paymentId), 'Payment not found.');
    }

    public function purchaseOrder(int $purchaseOrderId)
    {
        return $this->jsonDetail((new TransactionDetailService())->purchaseOrder($purchaseOrderId), 'RR / pickup not found.');
    }

    public function supplierOrder(int $supplierOrderId)
    {
        return $this->jsonDetail((new TransactionDetailService())->supplierOrder($supplierOrderId), 'Supplier PO not found.');
    }

    public function payable(int $payableId)
    {
        return $this->jsonDetail((new TransactionDetailService())->payable($payableId), 'CV / payable not found.');
    }

    private function jsonDetail(?array $detail, string $notFoundMessage)
    {
        if ($detail === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON(['error' => $notFoundMessage]);
        }

        return $this->response->setJSON($detail);
    }
}
