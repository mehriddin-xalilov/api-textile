<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\B2bLead;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** B2B kampaniya: xat ochilishi (piksel) va havola bosilishi (sayt) hisobi. */
class TrackController extends Controller
{
    /** Xat ichidagi 1x1 rasm: ochilganda chaqiriladi. Token noto'g'ri bo'lsa ham rasm qaytadi. */
    public function open(string $token): Response
    {
        B2bLead::query()->where('token', $token)->whereNull('opened_at')->update(['opened_at' => now()]);

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /** Sayt `?c=token` bilan ochilganda chaqiradi. */
    public function click(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:20'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        $lead = B2bLead::query()->where('token', $data['token'])->first();
        if ($lead) {
            $lead->forceFill([
                'first_click_at' => $lead->first_click_at ?? now(),
                'last_click_at' => now(),
                'clicks' => $lead->clicks + 1,
                'last_path' => $data['path'] ?? null,
                'last_ip' => $request->ip(),
                'user_id' => $lead->user_id ?? $request->user('api')?->id,
            ])->save();
        }

        return ApiResponse::item(['ok' => (bool) $lead]);
    }
}
