<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST → maydon majburiy. PUT/PATCH → yuborilsa majburiy, yuborilmasa tegilmaydi.
 * Admin panel jadvalidagi switch faqat {status} yuboradi — shu holat uchun.
 */
abstract class ApiFormRequest extends FormRequest
{
    /** @return list<string> */
    protected function required(): array
    {
        return $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];
    }
}
