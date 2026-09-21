<?php

namespace App\Http\Resources;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Permission */
class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_uz' => $this->name_uz,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'status' => $this->status,
        ];
    }
}
