<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePhraseTemplateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'text' => [...$this->required(), 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'font_family' => [...$this->required(), Rule::in(collect(config('fonts'))->pluck('family')->all())],
            'font_weight' => ['nullable', Rule::in(['400', '700', '900'])],
            'font_style' => ['nullable', Rule::in(['normal', 'italic'])],
            'fill' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
