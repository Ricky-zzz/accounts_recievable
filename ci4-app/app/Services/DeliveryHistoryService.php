<?php

namespace App\Services;

use App\Models\DeliveryHistoryModel;

class DeliveryHistoryService
{
    public function record(
        int $deliveryId,
        ?int $editedBy,
        string $action,
        ?array $oldDelivery,
        array $oldItems,
        ?array $newDelivery,
        array $newItems,
        string $summary
    ): void {
        (new DeliveryHistoryModel())->insert([
            'delivery_id' => $deliveryId,
            'edited_by' => $editedBy,
            'action' => $action,
            'old_delivery_json' => $oldDelivery === null ? null : $this->encode($oldDelivery),
            'old_items_json' => $this->encode($oldItems),
            'new_delivery_json' => $newDelivery === null ? null : $this->encode($newDelivery),
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
