<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductPriceController;
use Illuminate\Support\Facades\Route;

// Registered with the API_PREFIX prefix (see bootstrap/app.php).
Route::middleware('throttle:api')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::apiResource('currencies', CurrencyController::class)->whereNumber('currency');
        Route::apiResource('products', ProductController::class)->whereNumber('product');

        Route::get('/products/{product}/prices', [ProductPriceController::class, 'index'])
            ->whereNumber('product')
            ->name('products.prices.index');
        Route::post('/products/{product}/prices', [ProductPriceController::class, 'store'])
            ->whereNumber('product')
            ->name('products.prices.store');
    });
});
