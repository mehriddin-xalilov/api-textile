<?php

namespace App\Http\Resources;

use App\Models\PrintArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PrintArea */
class PrintAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'side' => $this->side,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'max_width_cm' => $this->max_width_cm,
            'max_height_cm' => $this->max_height_cm,
        ];
    }
}
