<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

it('revokes only the current token on logout', function (): void {
    $user = User::factory()->viewer()->create();
    $current = $user->createToken('current')->plainTextToken;
    $other = $user->createToken('other')->plainTextToken;

    $this->withToken($current)->postJson(route('auth.logout'))->assertNoContent();

    expect($user->tokens()->count())->toBe(1)
        ->and(PersonalAccessToken::findToken($other))->not->toBeNull();

    app('auth')->forgetGuards();

    $this->withToken($current)
        ->getJson(route('auth.me'))
        ->assertUnauthorized();
});

it('answers guests with a 401 envelope even without a JSON accept header', function (): void {
    $this->get(route('auth.me'), ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.', 'code' => 'unauthenticated']);
});

it('returns the authenticated user with roles and permissions', function (): void {
    $user = User::factory()->admin()->create();

    $this->withToken($user->createToken('t')->plainTextToken)
        ->getJson(route('auth.me'))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.roles', ['admin'])
        ->assertJsonCount(7, 'data.permissions');
});

it('rejects unauthenticated access with the error envelope', function (string $route, string $method): void {
    $this->json($method, route($route))
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.', 'code' => 'unauthenticated']);
})->with([
    'me' => ['auth.me', 'GET'],
    'logout' => ['auth.logout', 'POST'],
]);

it('rejects expired tokens', function (): void {
    $user = User::factory()->viewer()->create();
    $token = $user->createToken('t', ['*'], now()->subMinute())->plainTextToken;

    $this->withToken($token)->getJson(route('auth.me'))->assertUnauthorized();
});

it('resolves permissions for users authenticated through sanctum', function (): void {
    $path = '/'.config('app.api_prefix').'/__permissions';

    Route::middleware('auth:sanctum')->get($path, fn (Request $request): array => [
        'view' => $request->user()?->hasPermissionTo('products.view'),
        'create' => $request->user()?->hasPermissionTo('products.create'),
    ]);

    $viewer = User::factory()->viewer()->create();

    $this->withToken($viewer->createToken('t')->plainTextToken)
        ->getJson($path)
        ->assertOk()
        ->assertExactJson(['view' => true, 'create' => false]);
});
