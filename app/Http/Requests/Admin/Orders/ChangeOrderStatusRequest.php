<?php

namespace App\Http\Requests\Admin\Orders;

use App\Enums\OrderStatus;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Enum;

class ChangeOrderStatusRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => [...$this->required(), new Enum(OrderStatus::class)],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
