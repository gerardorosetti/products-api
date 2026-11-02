<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

final readonly class IssueTokenAction
{
    /**
     * Validates the credentials and issues a token whose abilities are the user's permissions.
     *
     * @throws ValidationException when the credentials are wrong (generic message, no user enumeration)
     */
    public function handle(string $email, string $password, ?string $deviceName = null): NewAccessToken
    {
        $user = User::query()->where('email', $email)->first();

        // Always hash-check, even for unknown emails, to keep response timing similar.
        $passwordMatches = Hash::check($password, $user instanceof User ? $user->password : Hash::make(Str::random(32)));

        if (! $user instanceof User || ! $passwordMatches) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        return $user->createToken(
            $deviceName ?? 'api',
            $user->getAllPermissions()->pluck('name')->all(),
            now()->addMinutes((int) config('sanctum.expiration')),
        );
    }
}
