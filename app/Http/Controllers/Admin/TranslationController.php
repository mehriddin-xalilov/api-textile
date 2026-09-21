<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Admin i18next backend: POST /translations/{locale} {message}
 * Yo'q kalit — avtomatik qo'shiladi (value = kalitning o'zi), keyin adminda tarjima qilinadi.
 */
class TranslationController extends Controller
{
    public function __invoke(Request $request, string $locale): JsonResponse
    {
        abort_unless(in_array($locale, ['uz', 'ru', 'en'], true), 404);

        $message = trim((string) $request->input('message', ''));

        if ($message !== '') {
            Translation::query()->firstOrCreate(['locale' => $locale, 'key' => $message], ['value' => $message]);
            Cache::forget("translations:{$locale}");
        }

        $all = Cache::remember("translations:{$locale}", 600, fn () => Translation::query()
            ->where('locale', $locale)
            ->pluck('value', 'key')
            ->all());

        return response()->json($all);
    }
}
