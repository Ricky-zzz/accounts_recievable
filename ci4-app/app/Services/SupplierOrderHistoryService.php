<?php

namespace App\Services;

use App\Models\SupplierOrderHistoryModel;

class SupplierOrderHistoryService
{
    public function record(
        int $supplierOrderId,
        ?int $editedBy,
        string $action,
        ?array $oldSupplierOrder,
        array $oldItems,
        ?array $newSupplierOrder,
        array $newItems,
        string $summary
    ): void {
        (new SupplierOrderHistoryModel())->insert([
            'supplier_order_id' => $supplierOrderId,
            'edited_by' => $editedBy,
            'action' => $action,
            'old_supplier_order_json' => $oldSupplierOrder === null ? null : $this->encode($oldSupplierOrder),
            'old_items_json' => $this->encode($oldItems),
            'new_supplier_order_json' => $newSupplierOrder === null ? null : $this->encode($newSupplierOrder),
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
