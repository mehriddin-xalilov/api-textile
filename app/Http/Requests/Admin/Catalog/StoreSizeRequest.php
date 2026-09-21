<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreSizeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [...$this->required(), 'string', 'max:16', Rule::unique('sizes')->ignore($this->route('size')?->id)],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
