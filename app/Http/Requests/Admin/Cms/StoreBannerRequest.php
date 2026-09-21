<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title_uz' => [...$this->required(), 'string', 'max:255'],
            'title_ru' => ['nullable', 'string', 'max:255'], 'title_en' => ['nullable', 'string', 'max:255'],
            'subtitle_uz' => ['nullable', 'string', 'max:500'], 'subtitle_ru' => ['nullable', 'string', 'max:500'], 'subtitle_en' => ['nullable', 'string', 'max:500'],
            'button_text_uz' => ['nullable', 'string', 'max:64'], 'button_text_ru' => ['nullable', 'string', 'max:64'], 'button_text_en' => ['nullable', 'string', 'max:64'],
            'link' => ['nullable', 'string', 'max:255'],
            'image_id' => ['nullable', 'integer', 'exists:files,id'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
