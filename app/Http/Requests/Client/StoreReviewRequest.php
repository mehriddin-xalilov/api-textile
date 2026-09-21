<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required_without_all:design_id,ready_product_id', 'nullable', 'integer', 'exists:products,id'],
            'ready_product_id' => ['nullable', 'integer', 'exists:ready_products,id'],
            'design_id' => ['nullable', 'integer', 'exists:designs,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
