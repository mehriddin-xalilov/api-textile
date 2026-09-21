<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'status' => $this->status,
            'locale' => $this->locale,
            'avatar' => new FileResource($this->whenLoaded('avatar')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            // Admin panel useAccess() uchun: ["users.list", "orders.view", ...]
            // Ikkala relation ham yuklangan bo'lsagina (strict mode: lazy load taqiqlangan)
            'permissions' => $this->when(
                $this->relationLoaded('roles') && $this->relationLoaded('permissions'),
                fn () => $this->getAllPermissions()->pluck('name')->values()
            ),
            'orders_count' => $this->whenCounted('orders'),
            'designs_count' => $this->whenCounted('designs'),
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
        ];
    }
}
