<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Page */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $full = $request->routeIs('*.pages.show') || $request->is('api/v1/admin/*');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translated('title'),
            'title_uz' => $this->title_uz, 'title_ru' => $this->title_ru, 'title_en' => $this->title_en,
            'content' => $this->when($full, fn () => $this->translated('content')),
            'content_uz' => $this->when($full, $this->content_uz), 'content_ru' => $this->when($full, $this->content_ru), 'content_en' => $this->when($full, $this->content_en),
            'in_footer' => $this->in_footer,
            'sort' => $this->sort,
            'status' => $this->status,
        ];
    }
}
