<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\ProductSide;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePrintAreaRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'side' => [...$this->required(), new Enum(ProductSide::class)],
            'name' => [...$this->required(), 'string', 'max:64'],
            'x' => [...$this->required(), 'numeric', 'between:0,100'],
            'y' => [...$this->required(), 'numeric', 'between:0,100'],
            'width' => [...$this->required(), 'numeric', 'between:1,100'],
            'height' => [...$this->required(), 'numeric', 'between:1,100'],
            'max_width_cm' => ['nullable', 'numeric', 'min:1'],
            'max_height_cm' => ['nullable', 'numeric', 'min:1'],
        ];
    }
}
