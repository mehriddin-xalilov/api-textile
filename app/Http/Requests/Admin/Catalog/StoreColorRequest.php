<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreColorRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name_uz' => [...$this->required(), 'string', 'max:64'],
            'name_ru' => ['nullable', 'string', 'max:64'],
            'name_en' => ['nullable', 'string', 'max:64'],
            'hex' => [...$this->required(), 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
