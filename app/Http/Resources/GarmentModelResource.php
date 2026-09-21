<?php

namespace App\Http\Resources;

use App\Models\GarmentModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GarmentModel */
class GarmentModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->translated('name'),
            'name_uz' => $this->name_uz,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'url' => $this->whenLoaded('file', fn () => $this->file->src),
            'file' => new FileResource($this->whenLoaded('file')),
            'thumbnail' => new FileResource($this->whenLoaded('thumbnail')),
            'zones' => $this->zones,
            'author' => $this->author,
            'sort' => $this->sort,
            'status' => $this->status,
            'products_count' => $this->whenCounted('products'),
        ];
    }
}
