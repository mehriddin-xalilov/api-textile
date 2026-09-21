<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use League\OAuth2\Server\Exception\OAuthServerException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api/v1/admin')
                ->middleware('api')
                ->name('admin.')
                ->group(base_path('routes/api/admin.php'));

            Route::prefix('api/v1')
                ->middleware('api')
                ->name('client.')
                ->group(base_path('routes/api/client.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [SetLocale::class]);
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Muddati o'tgan/bekor qilingan token — oddiy 401, log shart emas.
        $exceptions->dontReport(OAuthServerException::class);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => "Juda ko'p urinish. Bir daqiqadan so'ng qayta urinib ko'ring."], 429);
            }
        });

        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Ruxsat yo\'q'], 403);
            }
        });

        // API uchun HTTP xatolar (403, 404, 422 abort) — trace'siz, faqat {message}.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage() ?: 'Xatolik'], $e->getStatusCode());
            }
        });
    })->create();
