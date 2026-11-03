<?php

declare(strict_types=1);

use App\Exceptions\CurrencyInUseException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

function apiPath(string $path): string
{
    return '/'.config('app.api_prefix').'/'.ltrim($path, '/');
}

it('renders unknown API routes as a not_found envelope without leaking internals', function (): void {
    $response = $this->get(apiPath('does-not-exist'))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Resource not found.', 'code' => 'not_found']);

    expect($response->getContent())->not->toContain('Exception');
});

it('renders wrong methods as a method_not_allowed envelope', function (): void {
    Route::get(apiPath('__get-only'), fn () => 'ok');

    $this->putJson(apiPath('__get-only'))
        ->assertStatus(405)
        ->assertHeader('Allow')
        ->assertExactJson(['message' => 'Method not allowed.', 'code' => 'method_not_allowed']);
});

it('renders authorization failures as a forbidden envelope', function (): void {
    Route::get(apiPath('__forbidden'), fn () => throw new AuthorizationException);

    $this->getJson(apiPath('__forbidden'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});

it('renders currency in use as a conflict envelope', function (): void {
    Route::get(apiPath('__conflict'), fn () => throw new CurrencyInUseException);

    $this->getJson(apiPath('__conflict'))
        ->assertConflict()
        ->assertJsonPath('code', 'currency_in_use');
});

it('renders other http exceptions with http_error', function (): void {
    Route::get(apiPath('__teapot'), fn () => abort(418, 'I am a teapot'));

    $this->getJson(apiPath('__teapot'))
        ->assertStatus(418)
        ->assertExactJson(['message' => 'I am a teapot', 'code' => 'http_error']);
});

it('renders JSON even when the client does not ask for it', function (): void {
    $this->get(apiPath('does-not-exist'), ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});

it('hides details of unexpected errors when debug is off', function (): void {
    config(['app.debug' => false]);
    Route::get(apiPath('__boom'), fn () => throw new RuntimeException('database password is hunter2'));

    $response = $this->getJson(apiPath('__boom'))
        ->assertStatus(500)
        ->assertExactJson(['message' => 'Server error.', 'code' => 'server_error']);

    expect($response->getContent())->not->toContain('hunter2');
});

it('exposes the default debug payload for unexpected errors when debug is on', function (): void {
    config(['app.debug' => true]);
    Route::get(apiPath('__boom'), fn () => throw new RuntimeException('visible while debugging'));

    $this->getJson(apiPath('__boom'))
        ->assertStatus(500)
        ->assertJsonPath('message', 'visible while debugging');
});

it('renders every error as JSON when the API prefix is empty', function (): void {
    config(['app.api_prefix' => '']);

    $this->get('/anything/at/all', ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson(['message' => 'Resource not found.', 'code' => 'not_found']);
});

it('does not report expected currency-in-use conflicts to the log', function (): void {
    Log::spy();
    Route::get(apiPath('__quiet-conflict'), fn () => throw new CurrencyInUseException);

    $this->getJson(apiPath('__quiet-conflict'))->assertConflict();

    Log::shouldNotHaveReceived('error');
});
