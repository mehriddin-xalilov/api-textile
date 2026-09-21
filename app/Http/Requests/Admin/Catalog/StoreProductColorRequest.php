<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreProductColorRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')->id;
        $current = $this->route('color')?->id;

        return [
            'color_id' => [...$this->required(), 'integer', 'exists:colors,id',
                Rule::unique('product_colors')->where('product_id', $productId)->ignore($current)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'front_image_id' => ['nullable', 'integer', 'exists:files,id'],
            'back_image_id' => ['nullable', 'integer', 'exists:files,id'],
            'left_image_id' => ['nullable', 'integer', 'exists:files,id'],
            'right_image_id' => ['nullable', 'integer', 'exists:files,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'size_ids' => ['nullable', 'array'],
            'size_ids.*' => ['integer', 'exists:sizes,id'],
        ];
    }
}
