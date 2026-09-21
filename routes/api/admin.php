<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API — /api/v1/admin
|--------------------------------------------------------------------------
| Har bir resurs `permission:<resurs>.<amal>` bilan himoyalangan.
| Permission nomlari admin panel useAccess() bilan bir xil: users.list, users.create ...
*/

Route::post('auth/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('auth/refresh', [Admin\AuthController::class, 'refresh'])->middleware('throttle:30,1');
Route::post('translations/{locale}', Admin\TranslationController::class);

Route::middleware('auth:api')->group(function () {
    Route::get('get-me', [Admin\AuthController::class, 'me']);
    Route::post('auth/logout', [Admin\AuthController::class, 'logout']);
    Route::post('files', [Admin\FileController::class, 'store']);

    Route::get('dashboard', Admin\DashboardController::class)->middleware('permission:dashboard.view');
    Route::get('settings', [Admin\SettingController::class, 'index'])->middleware('permission:settings.list');
    Route::put('settings', [Admin\SettingController::class, 'update'])->middleware('permission:settings.update');

    Route::controller(Admin\UserController::class)->prefix('users')->group(function () {
        Route::get('/', 'index')->middleware('permission:users.list');
        Route::post('/', 'store')->middleware('permission:users.create');
        Route::get('{user}', 'show')->middleware('permission:users.view');
        Route::put('{user}', 'update')->middleware('permission:users.update');
        Route::delete('{user}', 'destroy')->middleware('permission:users.delete');
    });

    Route::controller(Admin\RoleController::class)->prefix('roles')->group(function () {
        Route::get('/', 'index')->middleware('permission:roles.list');
        Route::post('/', 'store')->middleware('permission:roles.create');
        Route::get('{role}', 'show')->middleware('permission:roles.view');
        Route::put('{role}', 'update')->middleware('permission:roles.update');
        Route::delete('{role}', 'destroy')->middleware('permission:roles.delete');
        Route::get('{role}/permissions', 'permissions')->middleware('permission:roles.view');
        Route::post('{role}/permissions', 'syncPermissions')->middleware('permission:roles.update');
    });

    Route::controller(Admin\PermissionController::class)->prefix('permissions')->group(function () {
        Route::get('/', 'index')->middleware('permission:permissions.list');
        Route::post('/', 'store')->middleware('permission:permissions.create');
        Route::put('{permission}', 'update')->middleware('permission:permissions.update');
        Route::delete('{permission}', 'destroy')->middleware('permission:permissions.delete');
    });

    // ── Katalog ────────────────────────────────────────────────────────────
    Route::put('{resource}/sort', Admin\SortController::class)
        ->whereIn('resource', ['categories', 'colors', 'sizes', 'products', 'cliparts', 'phrases', 'garment-models', 'banners', 'pages'])
        ->middleware('permission:products.update');

    foreach ([
        'categories' => [Admin\CategoryController::class, 'category'],
        'colors' => [Admin\ColorController::class, 'color'],
        'sizes' => [Admin\SizeController::class, 'size'],
        'cliparts' => [Admin\ClipartController::class, 'clipart'],
        'phrases' => [Admin\PhraseTemplateController::class, 'phrase'],
        'garment-models' => [Admin\GarmentModelController::class, 'garmentModel'],
        'banners' => [Admin\BannerController::class, 'banner'],
        'pages' => [Admin\PageController::class, 'page'],
    ] as $uri => [$controller, $param]) {
        Route::controller($controller)->prefix($uri)->group(function () use ($uri, $param, $controller) {
            Route::get('/', 'index')->middleware("permission:{$uri}.list");
            Route::post('/', 'store')->middleware("permission:{$uri}.create");
            if (method_exists($controller, 'show')) {
                Route::get("{{$param}}", 'show')->middleware("permission:{$uri}.view");
            }
            Route::put("{{$param}}", 'update')->middleware("permission:{$uri}.update");
            Route::delete("{{$param}}", 'destroy')->middleware("permission:{$uri}.delete");
        });
    }

    Route::controller(Admin\ProductController::class)->prefix('products')->group(function () {
        Route::get('/', 'index')->middleware('permission:products.list');
        Route::post('/', 'store')->middleware('permission:products.create');
        Route::get('{product}', 'show')->middleware('permission:products.view');
        Route::put('{product}', 'update')->middleware('permission:products.update');
        Route::delete('{product}', 'destroy')->middleware('permission:products.delete');
    });

    Route::prefix('products/{product}')->group(function () {
        Route::controller(Admin\ProductColorController::class)->prefix('colors')->group(function () {
            Route::get('/', 'index')->middleware('permission:products.view');
            Route::post('/', 'store')->middleware('permission:products.update');
            Route::put('{color}', 'update')->middleware('permission:products.update');
            Route::delete('{color}', 'destroy')->middleware('permission:products.update');
        });
        Route::controller(Admin\PrintAreaController::class)->prefix('print-areas')->group(function () {
            Route::get('/', 'index')->middleware('permission:products.view');
            Route::post('/', 'store')->middleware('permission:products.update');
            Route::put('{printArea}', 'update')->middleware('permission:products.update');
            Route::delete('{printArea}', 'destroy')->middleware('permission:products.update');
        });
    });

    // ── Ombor ──────────────────────────────────────────────────────────────
    Route::get('variants', [Admin\ProductVariantController::class, 'index'])->middleware('permission:inventory.list');
    Route::post('variants/{variant}/adjust', [Admin\ProductVariantController::class, 'adjust'])->middleware('permission:inventory.adjust');

    Route::controller(Admin\InventoryBatchController::class)->prefix('inventory-batches')->group(function () {
        Route::get('/', 'index')->middleware('permission:inventory-batches.list');
        Route::post('/', 'store')->middleware('permission:inventory-batches.create');
        Route::get('{batch}', 'show')->middleware('permission:inventory-batches.view');
        Route::put('{batch}', 'update')->middleware('permission:inventory-batches.update');
        Route::delete('{batch}', 'destroy')->middleware('permission:inventory-batches.delete');
        Route::post('{batch}/receive', 'receive')->middleware('permission:inventory-batches.receive');
    });

    // ── Dizayn va buyurtmalar ──────────────────────────────────────────────
    Route::get('designs', [Admin\DesignController::class, 'index'])->middleware('permission:designs.list');
    Route::get('designs/{design}', [Admin\DesignController::class, 'show'])->middleware('permission:designs.view')->name('designs.show');
    Route::put('designs/{design}', [Admin\DesignController::class, 'update'])->middleware('permission:designs.update')->name('designs.update');
    Route::post('designs/{design}/template', [Admin\DesignController::class, 'template'])->middleware('permission:designs.update');

    // ── Tayyor mahsulotlar (rasm bilan, konstruktorsiz) ──────────────────────
    Route::controller(Admin\ReadyProductController::class)->prefix('ready-products')->group(function () {
        Route::get('/', 'index')->middleware('permission:ready-products.list');
        Route::post('/', 'store')->middleware('permission:ready-products.create');
        Route::get('{ready_product}', 'show')->middleware('permission:ready-products.view');
        Route::put('{ready_product}', 'update')->middleware('permission:ready-products.update');
        Route::delete('{ready_product}', 'destroy')->middleware('permission:ready-products.delete');
    });

    // ── Izohlar (moderatsiya) ──────────────────────────────────────────────
    Route::controller(Admin\ReviewController::class)->prefix('reviews')->group(function () {
        Route::get('/', 'index')->middleware('permission:reviews.list');
        Route::put('{review}', 'update')->middleware('permission:reviews.update');
        Route::delete('{review}', 'destroy')->middleware('permission:reviews.delete');
    });

    Route::controller(Admin\OrderController::class)->prefix('orders')->group(function () {
        Route::get('/', 'index')->middleware('permission:orders.list');
        Route::get('{order}', 'show')->middleware('permission:orders.view');
        Route::post('{order}/status', 'changeStatus')->middleware('permission:orders.update');
        Route::post('{order}/payment', 'updatePayment')->middleware('permission:orders.update');
    });
});
