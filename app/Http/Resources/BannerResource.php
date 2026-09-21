<?php

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Banner */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->translated('title'),
            'subtitle' => $this->translated('subtitle'),
            'button_text' => $this->translated('button_text'),
            ...collect(['title', 'subtitle', 'button_text'])->flatMap(fn ($f) => collect(['uz', 'ru', 'en'])->mapWithKeys(fn ($l) => ["{$f}_{$l}" => $this->{"{$f}_{$l}"}]))->all(),
            'link' => $this->link,
            'image' => new FileResource($this->whenLoaded('image')),
            'sort' => $this->sort,
            'status' => $this->status,
        ];
    }
}
