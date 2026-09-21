<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Gender;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => [...$this->required(), 'integer', 'exists:categories,id'],
            'name_uz' => [...$this->required(), 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products')->ignore($this->route('product')?->id)],
            'description_uz' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'fabric' => ['nullable', 'string', 'max:120'],
            'origin_country' => ['nullable', Rule::in(['CN', 'TR'])],
            'gender' => ['nullable', new Enum(Gender::class)],
            'type' => ['nullable', Rule::in(['blank', 'finished'])],
            'garment_model_id' => ['nullable', 'integer', Rule::exists('garment_models', 'id')],
            'base_price' => [...$this->required(), 'numeric', 'min:0'],
            'print_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name_uz')) {
            $this->merge(['slug' => Str::slug($this->name_en ?: $this->name_uz).'-'.Str::lower(Str::random(4))]);
        }
    }
}
