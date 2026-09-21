<?php

namespace App\Http\Requests\Client\Auth;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['required', 'regex:/^\+998\d{9}$/', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone_number' => Phone::normalize((string) $this->phone_number)]);
    }
}
