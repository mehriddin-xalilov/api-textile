<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->translated('name'),
            'name_uz' => $this->name_uz,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'description' => $this->translated('description'),
            'description_uz' => $this->description_uz,
            'description_ru' => $this->description_ru,
            'description_en' => $this->description_en,
            'fabric' => $this->fabric,
            'origin_country' => $this->origin_country,
            'gender' => $this->gender,
            'type' => $this->type, // blank | finished
            'garment_model_id' => $this->garment_model_id,
            'garment_model' => new GarmentModelResource($this->whenLoaded('garmentModel')),
            'base_price' => $this->base_price,
            'print_price' => $this->print_price,
            'sort' => $this->sort,
            'status' => $this->status,
            'available_stock' => $this->when(isset($this->available_stock), fn () => (int) $this->available_stock),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'colors' => ProductColorResource::collection($this->whenLoaded('colors')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'print_areas' => PrintAreaResource::collection($this->whenLoaded('printAreas')),
            'rating' => $this->when(isset($this->approved_reviews_avg_rating), fn () => round((float) $this->approved_reviews_avg_rating, 2)),
            'reviews_count' => $this->when(isset($this->approved_reviews_count), fn () => (int) $this->approved_reviews_count),
            'created_at' => $this->created_at,
        ];
    }
}
