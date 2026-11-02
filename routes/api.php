<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\HealthController;
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
    });
});
