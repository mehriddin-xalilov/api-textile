<?php

namespace App\Http\Resources;

use App\Models\Design;
use App\Support\DesignCanvas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Design */
class DesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'product_color_id' => $this->product_color_id,
            'name' => $this->name,
            'status' => $this->status,
            'canvas' => $this->when($request->routeIs('*.designs.show') || $request->routeIs('*.designs.store') || $request->routeIs('*.designs.update') || $request->is('api/v1/admin/orders/*') || $request->routeIs('client.templates.show'), $this->canvas),
            // Admin/ishlab chiqarish uchun: qaysi tomonda, qaysi joyda, nima (matn+shrift yoki logo), necha sm.
            'summary' => $this->when(
                $this->relationLoaded('product') && $this->product->relationLoaded('printAreas'),
                fn () => DesignCanvas::summary($this->canvas ?? [], $this->product)
            ),
            'is_template' => $this->is_template,
            'template_title' => $this->template_title,
            'engine' => $this->canvas['engine'] ?? '2d',
            'print_files' => $this->canvas['print_files'] ?? [],
            'preview' => new FileResource($this->whenLoaded('preview')),
            'photo' => new FileResource($this->whenLoaded('photo')),
            'print_file' => new FileResource($this->whenLoaded('printFile')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'product_color' => new ProductColorResource($this->whenLoaded('productColor')),
            'user' => new UserResource($this->whenLoaded('user')),
            'rating' => $this->when(isset($this->approved_reviews_avg_rating), fn () => round((float) $this->approved_reviews_avg_rating, 2)),
            'reviews_count' => $this->when(isset($this->approved_reviews_count), fn () => (int) $this->approved_reviews_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
