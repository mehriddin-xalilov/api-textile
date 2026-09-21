<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => response()->json(['app' => config('app.name'), 'api' => url('/api/v1')]));

/*
| Storage fayllari (mockup, logo, preview) — CORS bilan.
| Konstruktor (boshqa origin) rasmlarni canvas'ga chizadi va SVG matnini o'qiydi,
| shuning uchun Access-Control-Allow-Origin shart. `storage:link` symlink ishlatilmaydi.
| Prod'da nginx to'g'ridan-to'g'ri bersa ham xuddi shu sarlavhani qo'shsin.
*/
Route::get('storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path), [
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=31536000, immutable',
        // SVG ichida skript bo'lsa ham bajarilmasin; fayl HTML sifatida ochilmasin
        'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:",
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->where('path', '.*')->name('storage');
