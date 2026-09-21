<?php

namespace App\Http\Resources;

use App\Models\PhraseTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PhraseTemplate */
class PhraseTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'category' => $this->category,
            'font_family' => $this->font_family,
            'font_weight' => $this->font_weight,
            'font_style' => $this->font_style,
            'fill' => $this->fill,
            'sort' => $this->sort,
            'status' => $this->status,
        ];
    }
}
