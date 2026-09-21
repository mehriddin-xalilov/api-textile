<?php

use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Client;
use App\Http\Controllers\Payments\PaymentController;
use App\Models\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client API — /api/v1  (sayt va mobil ilova)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->controller(Client\AuthController::class)->group(function () {
    Route::post('register', 'register')->middleware('throttle:20,1');
    Route::post('login', 'login')->middleware('throttle:30,1');
    Route::post('check', 'check')->middleware('throttle:30,1');
    Route::post('refresh', 'refresh')->middleware('throttle:30,1');
    Route::middleware('auth:api')->group(function () {
        Route::get('me', 'me');
        Route::post('logout', 'logout');
    });
});

Route::get('files/{file}', fn (File $file) => redirect($file->src));
// To'lov callback'lari (auth yo'q — provayder chaqiradi)
Route::post('payments/payme', [PaymentController::class, 'payme'])->withoutMiddleware('throttle:api');
Route::post('payments/click/prepare', [PaymentController::class, 'click']);
Route::post('payments/click/complete', [PaymentController::class, 'click']);

Route::get('fonts', fn () => response()->json(['data' => config('fonts')]));
Route::get('cliparts', [Client\CatalogController::class, 'cliparts']);
Route::get('phrases', [Client\CatalogController::class, 'phrases']);
Route::get('site', [Client\SiteController::class, 'index']);
Route::get('pages/{page:slug}', [Client\SiteController::class, 'page'])->name('pages.show');
Route::get('templates', [Client\CatalogController::class, 'templates']);
Route::get('templates/{design}', [Client\CatalogController::class, 'template'])->name('templates.show');

Route::get('ready-products', [Client\ReadyProductController::class, 'index']);
Route::get('ready-products/{slug}', [Client\ReadyProductController::class, 'show']);

Route::controller(Client\ReviewController::class)->prefix('reviews')->group(function () {
    Route::get('/', 'index');
    Route::get('summary', 'summary');
});

Route::controller(Client\CatalogController::class)->group(function () {
    Route::get('categories', 'categories');
    Route::get('products', 'products');
    Route::get('products/{slug}', 'product');
});

Route::middleware('auth:api')->group(function () {
    Route::post('files', [FileController::class, 'store'])->middleware('throttle:60,1');

    Route::apiResource('designs', Client\DesignController::class);

    // Saqlangan manzillar
    Route::controller(Client\AddressController::class)->prefix('addresses')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('{address}', 'update');
        Route::delete('{address}', 'destroy');
    });

    Route::put('auth/profile', [Client\AuthController::class, 'updateProfile']);

    Route::controller(Client\ReviewController::class)->prefix('reviews')->group(function () {
        Route::get('mine', 'mine');
        Route::post('/', 'store')->middleware('throttle:20,1');
        Route::delete('{review}', 'destroy');
    });

    Route::controller(Client\OrderController::class)->prefix('orders')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store')->middleware('throttle:20,1');
        Route::get('{order}', 'show');
        Route::post('{order}/cancel', 'cancel');
        Route::get('{order}/pay-url', [PaymentController::class, 'payUrl']);
        Route::get('{order}/payment-status', [PaymentController::class, 'status']);
    });
});
