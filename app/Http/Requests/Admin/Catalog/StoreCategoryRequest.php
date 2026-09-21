<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$this->route('category')?->id])],
            'name_uz' => [...$this->required(), 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories')->ignore($this->route('category')?->id)],
            'image_id' => ['nullable', 'integer', 'exists:files,id'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name_uz')) {
            $this->merge(['slug' => Str::slug($this->name_en ?: $this->name_uz)]);
        }
    }
}
