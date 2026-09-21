<?php

namespace App\Http\Requests\Admin\Review;

use App\Enums\ReviewStatus;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewRequest extends ApiFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => [...$this->required(), Rule::enum(ReviewStatus::class)],
            'reply' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
