<?php

namespace App\Http\Requests\Admin\Users;

use App\Http\Requests\ApiFormRequest;
use App\Support\Phone;
use Illuminate\Validation\Rule;

class StoreUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'first_name' => [...$this->required(), 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone_number' => [...$this->required(), 'regex:/^\+998\d{9}$/', Rule::unique('users')->ignore($userId)->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($userId)->whereNull('deleted_at')],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:6', 'max:64'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'locale' => ['nullable', Rule::in(['uz', 'ru', 'en'])],
            'avatar_id' => ['nullable', 'integer', 'exists:files,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone_number')) {
            $this->merge(['phone_number' => Phone::normalize((string) $this->phone_number)]);
        }
    }
}
