<?php

namespace App\Http\Requests\Client\Addresses;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('recipient_phone')) {
            $this->merge(['recipient_phone' => Phone::normalize($this->input('recipient_phone'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => [$required, 'string', 'max:255'],
            'recipient_phone' => [$required, 'string', 'max:20'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'street' => [$required, 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
