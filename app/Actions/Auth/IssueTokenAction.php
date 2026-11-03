<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

        // Exactly one hash check per attempt, even for unknown emails, to keep response timing similar.
        $passwordMatches = Hash::check($password, $user instanceof User ? $user->password : $this->dummyHash());

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

    /**
     * A throwaway hash made with the app's current hashing cost, generated once and cached,
     * so unknown emails cost the same single check as known ones.
     */
    private function dummyHash(): string
    {
        return Cache::rememberForever(
            'auth.dummy_password_hash',
            static fn (): string => Hash::make(Str::random(32)),
        );
    }
}
