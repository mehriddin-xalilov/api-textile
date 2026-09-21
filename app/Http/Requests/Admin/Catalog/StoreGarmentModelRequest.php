<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreGarmentModelRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $zone = ['nullable', 'array'];

        return [
            'name_uz' => [...$this->required(), 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'file_id' => [...$this->required(), 'integer', 'exists:files,id'],
            'thumbnail_id' => ['nullable', 'integer', 'exists:files,id'],
            'author' => ['nullable', 'string', 'max:255'],
            'zones' => ['nullable', 'array'],
            'zones.front' => $zone, 'zones.back' => $zone, 'zones.sleeve_left' => $zone, 'zones.sleeve_right' => $zone,
            'zones.*.position' => ['nullable', 'array', 'size:3'],
            'zones.*.rotation' => ['nullable', 'array', 'size:3'],
            'zones.*.scale' => ['nullable', 'numeric', 'between:0.01,5'],
            'zones.*.*.*' => ['numeric'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Admin formasidan JSON matn kelishi mumkin
        if (is_string($this->zones)) {
            $this->merge(['zones' => json_decode($this->zones, true) ?: null]);
        }
    }
}
