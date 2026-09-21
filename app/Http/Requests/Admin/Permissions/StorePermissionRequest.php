<?php

namespace App\Http\Requests\Admin\Permissions;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [...$this->required(), 'string', 'max:64', 'regex:/^[a-z0-9_-]+\.[a-z0-9_-]+$/', Rule::unique('permissions')->ignore($this->route('permission')?->id)],
            'name_uz' => [...$this->required(), 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
