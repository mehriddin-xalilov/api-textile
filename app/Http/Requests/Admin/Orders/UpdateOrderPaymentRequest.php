<?php

namespace App\Http\Requests\Admin\Orders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderPaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'payment_status' => [...$this->required(), new Enum(PaymentStatus::class)],
            'payment_method' => ['nullable', new Enum(PaymentMethod::class)],
        ];
    }
}
