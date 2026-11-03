<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\IssueTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    /**
     * Log in
     *
     * Exchanges credentials for an opaque bearer token. The token abilities match the user's permissions.
     */
    #[ApiResponse(200, 'The bearer token and the authenticated user.')]
    public function login(LoginRequest $request, IssueTokenAction $issueToken): LoginResource
    {
        $token = $issueToken->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->filled('device_name') ? $request->string('device_name')->toString() : null,
        );

        return new LoginResource($token);
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
