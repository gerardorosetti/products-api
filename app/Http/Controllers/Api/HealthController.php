<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Health\CheckDatabaseAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    /**
     * Health check
     *
     * Reports whether the application and its database are reachable.
     */
    public function __invoke(CheckDatabaseAction $checkDatabase): JsonResponse
    {
        if ($checkDatabase->handle()) {
            return response()->json(['status' => 'ok', 'database' => 'ok']);
        }

        return response()->json(['status' => 'degraded', 'database' => 'unavailable'], 503);
    }
}
