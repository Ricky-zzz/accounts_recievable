<?php

namespace App\Services;

use App\Models\PurchaseOrderHistoryModel;

class PurchaseOrderHistoryService
{
    public function record(
        int $purchaseOrderId,
        ?int $editedBy,
        string $action,
        ?array $oldPurchaseOrder,
        array $oldItems,
        ?array $newPurchaseOrder,
        array $newItems,
        string $summary
    ): void {
        (new PurchaseOrderHistoryModel())->insert([
            'purchase_order_id' => $purchaseOrderId,
            'edited_by' => $editedBy,
            'action' => $action,
            'old_purchase_order_json' => $oldPurchaseOrder === null ? null : $this->encode($oldPurchaseOrder),
            'old_items_json' => $this->encode($oldItems),
            'new_purchase_order_json' => $newPurchaseOrder === null ? null : $this->encode($newPurchaseOrder),
            'new_items_json' => $this->encode($newItems),
            'change_summary' => $summary,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
