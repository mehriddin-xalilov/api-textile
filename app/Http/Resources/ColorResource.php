<?php

namespace App\Http\Resources;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Color */
class ColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->translated('name'),
            'name_uz' => $this->name_uz,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'hex' => $this->hex,
            'sort' => $this->sort,
            'status' => $this->status,
        ];
    }
}
