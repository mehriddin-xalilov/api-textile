<?php

namespace App\Http\Resources;

use App\Models\ReadyProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReadyProduct */
class ReadyProductResource extends JsonResource
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
            'price' => $this->price,
            'old_price' => $this->old_price,
            'color_name' => $this->color_name,
            'color_hex' => $this->color_hex,
            'sizes' => $this->sizes ?? [],
            'specs' => $this->specs ?? [],
            'sold_count' => $this->sold_count,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'sort' => $this->sort,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => [
                'id' => $i->id,
                'file_id' => $i->file_id,
                'src' => $i->file?->src,
            ])->all()),
            'image' => $this->whenLoaded('images', fn () => $this->images->first()?->file?->src),
            'rating' => $this->when(isset($this->approved_reviews_avg_rating), fn () => round((float) $this->approved_reviews_avg_rating, 2)),
            'reviews_count' => $this->when(isset($this->approved_reviews_count), fn () => (int) $this->approved_reviews_count),
            'created_at' => $this->created_at,
        ];
    }
}
