<?php

namespace App\Http\Resources;

use App\Models\InventoryBatchItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryBatchItem */
class InventoryBatchItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
        ];
    }
}
