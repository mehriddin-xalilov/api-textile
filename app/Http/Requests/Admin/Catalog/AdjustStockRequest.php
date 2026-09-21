<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;

class AdjustStockRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => [...$this->required(), 'integer', 'not_in:0'], // ishorali: +5 / -3
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
