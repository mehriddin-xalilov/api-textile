<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'slug' => [...$this->required(), 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages')->ignore($this->route('page')?->id)],
            'title_uz' => [...$this->required(), 'string', 'max:255'],
            'title_ru' => ['nullable', 'string', 'max:255'], 'title_en' => ['nullable', 'string', 'max:255'],
            'content_uz' => ['nullable', 'string'], 'content_ru' => ['nullable', 'string'], 'content_en' => ['nullable', 'string'],
            'in_footer' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
