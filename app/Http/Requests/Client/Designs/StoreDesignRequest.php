<?php

namespace App\Http\Requests\Client\Designs;

use App\Models\Product;
use App\Support\DesignCanvas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDesignRequest extends FormRequest
{
    public function rules(): array
    {
        $product = Product::query()->find($this->input('product_id'));

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_color_id' => ['required', 'integer',
                Rule::exists('product_colors', 'id')->where('product_id', $this->input('product_id'))],
            'name' => ['nullable', 'string', 'max:255'],
            'canvas' => ['required', 'array'],
            'preview_file_id' => ['nullable', 'integer', 'exists:files,id'],
            'print_file_id' => ['nullable', 'integer', 'exists:files,id'],
        ] + DesignCanvas::rules($product);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $product = Product::query()->find($this->input('product_id'));
                if (! $product || $validator->errors()->has('canvas')) {
                    return;
                }
                if ($bad = DesignCanvas::sidesMismatch($this->input('canvas', []), $product)) {
                    $validator->errors()->add('canvas.sides', 'Bu mahsulotda tomon yo\'q: '.implode(', ', $bad));
                }
            },
        ];
    }
}
