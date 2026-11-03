<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;

function currencyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Swiss Franc',
        'code' => 'CHF',
        'symbol' => 'Fr',
        'exchange_rate' => '0.88',
    ], $overrides);
}

it('requires authentication on every currency route', function (string $method, string $route, array $params): void {
    $this->json($method, route($route, $params))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
})->with([
    'index' => ['GET', 'currencies.index', []],
    'show' => ['GET', 'currencies.show', [1]],
    'store' => ['POST', 'currencies.store', []],
    'update' => ['PUT', 'currencies.update', [1]],
    'destroy' => ['DELETE', 'currencies.destroy', [1]],
]);

it('lists currencies for a viewer', function (): void {
    actingAsViewer();
    Currency::factory()->count(3)->create();

    $this->getJson(route('currencies.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'code', 'symbol', 'exchange_rate']]]);
});

it('shows a currency with the exchange rate as an 8 decimal string', function (): void {
    actingAsViewer();
    $currency = Currency::factory()->create(['exchange_rate' => 1]);

    $this->getJson(route('currencies.show', $currency))
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $currency->id,
            'name' => $currency->name,
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'exchange_rate' => '1.00000000',
        ]]);
});

it('returns 404 for an unknown currency', function (): void {
    actingAsViewer();

    $this->getJson(route('currencies.show', 999))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Resource not found.', 'code' => 'not_found']);
});

it('creates a currency and normalizes the code to uppercase', function (): void {
    actingAsAdmin();

    $this->postJson(route('currencies.store'), currencyPayload(['code' => 'chf']))
        ->assertCreated()
        ->assertJsonPath('data.code', 'CHF')
        ->assertJsonPath('data.exchange_rate', '0.88000000');

    expect(Currency::query()->where('code', 'CHF')->exists())->toBeTrue();
});

it('forbids a viewer from every currency write', function (string $method, string $route): void {
    actingAsViewer();
    $currency = Currency::factory()->create();

    $this->json($method, route($route, $route === 'currencies.store' ? [] : [$currency]), currencyPayload())
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
})->with([
    'store' => ['POST', 'currencies.store'],
    'update' => ['PUT', 'currencies.update'],
    'patch' => ['PATCH', 'currencies.update'],
    'destroy' => ['DELETE', 'currencies.destroy'],
]);

it('validates the currency payload on create', function (array $overrides, string $field): void {
    actingAsAdmin();
    Currency::factory()->create(['code' => 'EUR']);

    $this->postJson(route('currencies.store'), currencyPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors($field);
})->with([
    'missing name' => [['name' => null], 'name'],
    'name too long' => [['name' => str_repeat('a', 101)], 'name'],
    'missing code' => [['code' => null], 'code'],
    'code too short' => [['code' => 'AB'], 'code'],
    'code with digits' => [['code' => 'A1B'], 'code'],
    'duplicate code' => [['code' => 'eur'], 'code'],
    'missing symbol' => [['symbol' => null], 'symbol'],
    'symbol too long' => [['symbol' => str_repeat('s', 11)], 'symbol'],
    'missing rate' => [['exchange_rate' => null], 'exchange_rate'],
    'zero rate' => [['exchange_rate' => 0], 'exchange_rate'],
    'negative rate' => [['exchange_rate' => -1], 'exchange_rate'],
    'too many decimals' => [['exchange_rate' => '1.123456789'], 'exchange_rate'],
    'non numeric rate' => [['exchange_rate' => 'abc'], 'exchange_rate'],
    'rate too large' => [['exchange_rate' => '99999999999'], 'exchange_rate'],
]);

it('replaces a currency fully with PUT', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create(['code' => 'AAA']);

    $this->putJson(route('currencies.update', $currency), currencyPayload(['code' => 'BBB']))
        ->assertOk()
        ->assertJsonPath('data.code', 'BBB')
        ->assertJsonPath('data.name', 'Swiss Franc');
});

it('requires every field on PUT', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();

    $this->putJson(route('currencies.update', $currency), ['name' => 'Only name'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'symbol', 'exchange_rate']);
});

it('updates only the sent fields with PATCH', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create(['code' => 'AAA', 'symbol' => '#']);

    $this->patchJson(route('currencies.update', $currency), ['exchange_rate' => '2.5'])
        ->assertOk()
        ->assertJsonPath('data.exchange_rate', '2.50000000')
        ->assertJsonPath('data.code', 'AAA')
        ->assertJsonPath('data.symbol', '#');
});

it('allows keeping the own code on update but rejects another currency code', function (): void {
    actingAsAdmin();
    Currency::factory()->create(['code' => 'EUR']);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $this->patchJson(route('currencies.update', $currency), ['code' => 'USD'])->assertOk();
    $this->patchJson(route('currencies.update', $currency), ['code' => 'EUR'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('deletes an unused currency', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();

    $this->deleteJson(route('currencies.destroy', $currency))->assertNoContent();

    expect(Currency::query()->find($currency->id))->toBeNull();
});

it('refuses to delete a currency used as a product base currency', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();
    Product::factory()->create(['currency_id' => $currency->id]);

    $this->deleteJson(route('currencies.destroy', $currency))
        ->assertConflict()
        ->assertJsonPath('code', 'currency_in_use');
});

it('refuses to delete a currency used by a soft-deleted product', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();
    Product::factory()->create(['currency_id' => $currency->id])->delete();

    $this->deleteJson(route('currencies.destroy', $currency))->assertConflict();
});

it('refuses to delete a currency used by a product price', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();
    ProductPrice::factory()->create(['currency_id' => $currency->id]);

    $this->deleteJson(route('currencies.destroy', $currency))
        ->assertConflict()
        ->assertJsonPath('code', 'currency_in_use');
});

it('keeps full precision for an exchange rate sent as a JSON number', function (): void {
    actingAsAdmin();

    $this->postJson(route('currencies.store'), ['name' => 'Test', 'code' => 'TST', 'symbol' => 'T', 'exchange_rate' => 1234567.12345678])
        ->assertCreated()
        ->assertJsonPath('data.exchange_rate', '1234567.12345678');
});
