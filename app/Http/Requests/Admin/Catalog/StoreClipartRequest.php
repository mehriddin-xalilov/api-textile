<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreClipartRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name_uz' => [...$this->required(), 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'file_id' => [...$this->required(), 'integer', 'exists:files,id'],
            'recolorable' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
