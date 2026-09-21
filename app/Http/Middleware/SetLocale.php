<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Accept-Language yoki ?lang= orqali javob tili. */
class SetLocale
{
    private const SUPPORTED = ['uz', 'ru', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang', $request->header('Accept-Language', 'uz'));
        $locale = substr((string) $locale, 0, 2);

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'uz');

        return $next($request);
    }
}
