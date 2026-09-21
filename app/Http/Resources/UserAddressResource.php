<?php

namespace App\Http\Resources;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserAddress */
class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'region' => $this->region,
            'city' => $this->city,
            'street' => $this->street,
            'apartment' => $this->apartment,
            'landmark' => $this->landmark,
            'note' => $this->note,
            'is_default' => $this->is_default,
            'full' => $this->full,
            'created_at' => $this->created_at,
        ];
    }
}
