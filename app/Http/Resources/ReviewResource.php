<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reply' => $this->reply,
            'status' => $this->status,
            'product_id' => $this->product_id,
            'ready_product_id' => $this->ready_product_id,
            'design_id' => $this->design_id,
            'order_id' => $this->order_id,
            // Saytda faqat ism ko'rinadi (familiyaning bosh harfi bilan)
            'author' => $this->whenLoaded('user', fn () => trim($this->user->first_name.' '.mb_substr((string) $this->user->last_name, 0, 1).'.')),
            'user' => new UserResource($this->whenLoaded('user')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
        ];
    }
}
