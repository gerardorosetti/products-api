<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\IssueTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    /**
     * Log in
     *
     * Exchanges credentials for an opaque bearer token. The token abilities match the user's permissions.
     */
    public function login(LoginRequest $request, IssueTokenAction $issueToken): JsonResponse
    {
        $token = $issueToken->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->filled('device_name') ? $request->string('device_name')->toString() : null,
        );

        /** @var User $user */
        $user = $token->accessToken->tokenable;

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
                'user' => (new UserResource($user))->resolve($request),
            ],
        ]);
    }

    /**
     * Log out
     *
     * Revokes the token used for this request only.
     */
    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Current user
     */
    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user);
    }
}
