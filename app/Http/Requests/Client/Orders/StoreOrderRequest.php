<?php

namespace App\Http\Requests\Client\Orders;

use App\Enums\PaymentMethod;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            // Qator yo konstruktor variantidan, yo tayyor mahsulotdan
            'items.*.product_variant_id' => ['required_without:items.*.ready_product_id', 'nullable', 'integer', 'exists:product_variants,id'],
            'items.*.ready_product_id' => ['required_without:items.*.product_variant_id', 'nullable', 'integer', 'exists:ready_products,id'],
            'items.*.size' => ['nullable', 'string', 'max:10'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'items.*.design_id' => ['nullable', 'integer', 'exists:designs,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'regex:/^\+998\d{9}$/'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
            // Naqd yo'q: mijoz buyurtmadan keyin darhol onlayn to'laydi
            'payment_method' => ['required', Rule::in([PaymentMethod::Payme->value, PaymentMethod::Click->value, PaymentMethod::Uzum->value])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('recipient_phone')) {
            $this->merge(['recipient_phone' => Phone::normalize((string) $this->recipient_phone)]);
        }
    }
}
