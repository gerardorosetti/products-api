<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('logs in and returns a working bearer token', function (): void {
    $response = $this->postJson(route('auth.login'), [
        'email' => 'viewer@example.com',
        'password' => 'Password123!',
        'device_name' => 'phpunit',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'expires_at',
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
            ],
        ])
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'viewer@example.com')
        ->assertJsonPath('data.user.roles', ['viewer']);

    $token = $response->json('data.token');

    $this->withToken($token)
        ->getJson(route('auth.me'))
        ->assertOk()
        ->assertJsonPath('data.email', 'viewer@example.com');
});

it('issues tokens whose abilities match the user permissions', function (): void {
    $token = $this->postJson(route('auth.login'), [
        'email' => 'viewer@example.com',
        'password' => 'Password123!',
    ])->json('data.token');

    $accessToken = PersonalAccessToken::findToken($token);

    expect($accessToken)->not->toBeNull()
        ->and($accessToken->name)->toBe('api')
        ->and($accessToken->abilities)->toEqualCanonicalizing(['products.view', 'currencies.view']);
});

it('sets the token expiration from the sanctum configuration', function (): void {
    config(['sanctum.expiration' => 60]);

    $this->travelTo(now()->startOfSecond());

    $response = $this->postJson(route('auth.login'), [
        'email' => 'admin@example.com',
        'password' => 'Password123!',
    ])->assertOk();

    expect(Carbon::parse($response->json('data.expires_at'))->equalTo(now()->addMinutes(60)))->toBeTrue();
});

it('rejects a wrong password with a generic validation error', function (): void {
    $this->postJson(route('auth.login'), [
        'email' => 'viewer@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertExactJson([
            'message' => 'The provided credentials are incorrect.',
            'code' => 'validation_failed',
            'errors' => ['email' => ['The provided credentials are incorrect.']],
        ]);
});

it('answers an unknown email exactly like a wrong password', function (): void {
    $known = $this->postJson(route('auth.login'), [
        'email' => 'viewer@example.com',
        'password' => 'wrong-password',
    ]);

    $unknown = $this->postJson(route('auth.login'), [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $unknown->assertUnprocessable();
    expect($unknown->json())->toBe($known->json());
});

it('validates the login payload per field', function (array $payload, string $field): void {
    $this->postJson(route('auth.login'), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors($field);
})->with([
    'missing email' => [['password' => 'x'], 'email'],
    'invalid email' => [['email' => 'not-an-email', 'password' => 'x'], 'email'],
    'email too long' => [['email' => str_repeat('a', 250).'@b.co', 'password' => 'x'], 'email'],
    'missing password' => [['email' => 'viewer@example.com'], 'password'],
    'non-string password' => [['email' => 'viewer@example.com', 'password' => ['x']], 'password'],
    'device name too long' => [['email' => 'viewer@example.com', 'password' => 'x', 'device_name' => str_repeat('d', 101)], 'device_name'],
]);

it('throttles login after five attempts', function (): void {
    $payload = ['email' => 'viewer@example.com', 'password' => 'wrong-password'];

    foreach (range(1, 5) as $_) {
        $this->postJson(route('auth.login'), $payload)->assertUnprocessable();
    }

    $this->postJson(route('auth.login'), $payload)
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('code', 'too_many_requests')
        ->assertJsonStructure(['message', 'code']);
});

it('seeds exactly the two demo users', function (): void {
    expect(User::query()->count())->toBe(2);
});

it('runs exactly one hash check for an unknown email and never hashes per request', function (): void {
    // Warm the cached dummy hash so only the steady-state behaviour is measured.
    $this->postJson(route('auth.login'), ['email' => 'ghost@example.com', 'password' => 'whatever-123'])
        ->assertUnprocessable();

    Hash::spy();

    $this->postJson(route('auth.login'), ['email' => 'ghost@example.com', 'password' => 'whatever-123'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

    Hash::shouldNotHaveReceived('make');
    Hash::shouldHaveReceived('check')->once();
});
