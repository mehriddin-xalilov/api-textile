<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_color_id' => $this->product_color_id,
            'size_id' => $this->size_id,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'reserved' => $this->reserved,
            'available' => $this->available,
            'size' => new SizeResource($this->whenLoaded('size')),
            'product_color' => new ProductColorResource($this->whenLoaded('productColor')),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
