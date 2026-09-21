<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Sayt sozlamalari: aloqa, ijtimoiy tarmoqlar, yetkazib berish matni. GET → {key: value}, PUT {settings: {key: value}} */
class SettingController extends Controller
{
    public const KEYS = ['phone', 'telegram', 'instagram', 'email', 'address_uz', 'address_ru', 'address_en', 'work_hours', 'delivery_text_uz', 'delivery_text_ru', 'delivery_text_en', 'map_url'];

    public function index(): JsonResponse
    {
        return ApiResponse::item(array_merge(array_fill_keys(self::KEYS, null), Setting::all_()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string', 'max:2000']]);
        foreach ($data['settings'] as $key => $value) {
            if (in_array($key, self::KEYS, true)) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'contact']);
            }
        }
        Setting::flush();

        return $this->index();
    }
}
