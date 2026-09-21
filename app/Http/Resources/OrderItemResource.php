<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'design_id' => $this->design_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            // Ro'yxatdagi kichik rasm: dizayn preview yoki tayyor mahsulot fotosi
            'thumbnail' => $this->when(
                $this->relationLoaded('design') || $this->relationLoaded('readyProduct'),
                fn () => $this->design?->preview?->src ?? $this->readyProduct?->images->first()?->file?->src,
            ),
            'color_name' => $this->color_name,
            'color_hex' => $this->color_hex,
            'size_name' => $this->size_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'print_price' => $this->print_price,
            'total_price' => $this->total_price,
            'print_file' => new FileResource($this->whenLoaded('printFile')),
            'design' => new DesignResource($this->whenLoaded('design')),
        ];
    }
}
