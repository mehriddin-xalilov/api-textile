<?php

namespace App\Http\Requests\Admin\Inventory;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryBatchRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'supplier_country' => [...$this->required(), Rule::in(['CN', 'TR'])],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'arrived_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.product_variant_id' => [...$this->required(), 'integer', 'exists:product_variants,id', 'distinct'],
            'items.*.quantity' => [...$this->required(), 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
