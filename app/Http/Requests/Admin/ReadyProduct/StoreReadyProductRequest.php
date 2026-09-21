<?php

namespace App\Http\Requests\Admin\ReadyProduct;

use App\Enums\Status;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreReadyProductRequest extends ApiFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_uz' => [...$this->required(), 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('ready_products', 'slug')->ignore($this->route('ready_product'))],
            'description_uz' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price' => [...$this->required(), 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'color_name' => ['nullable', 'string', 'max:100'],
            'color_hex' => ['nullable', 'string', 'max:9'],
            'sizes' => ['nullable', 'array', 'max:20'],
            'sizes.*' => ['string', 'max:10'],
            'specs' => ['nullable', 'array', 'max:40'],
            'specs.*.name' => ['required', 'string', 'max:100'],
            'specs.*.value' => ['required', 'string', 'max:200'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'sold_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(Status::class)],
            'sort' => ['nullable', 'integer'],
            'image_ids' => ['nullable', 'array', 'max:10'],
            'image_ids.*' => ['integer', 'exists:files,id'],
        ];
    }
}
