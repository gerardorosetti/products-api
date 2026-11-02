<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): JsonResponse => response()->json([
    'name' => config('app.name'),
    'docs' => url('/docs/api'),
]))->name('root');
