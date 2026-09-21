<?php

namespace App\Http\Resources;

use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryBatch */
class InventoryBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'supplier_country' => $this->supplier_country,
            'supplier_name' => $this->supplier_name,
            'arrived_at' => $this->arrived_at?->toDateString(),
            'status' => $this->status,
            'note' => $this->note,
            'received_at' => $this->received_at,
            'items_count' => $this->whenCounted('items'),
            'total_quantity' => $this->when(isset($this->items_sum_quantity), fn () => (int) $this->items_sum_quantity),
            'items' => InventoryBatchItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
