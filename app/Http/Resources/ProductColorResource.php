<?php

namespace App\Http\Resources;

use App\Models\ProductColor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductColor */
class ProductColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'color_id' => $this->color_id,
            'price' => $this->price,
            'status' => $this->status,
            'color' => new ColorResource($this->whenLoaded('color')),
            'front_image' => new FileResource($this->whenLoaded('frontImage')),
            'back_image' => new FileResource($this->whenLoaded('backImage')),
            'left_image' => new FileResource($this->whenLoaded('leftImage')),
            'right_image' => new FileResource($this->whenLoaded('rightImage')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
