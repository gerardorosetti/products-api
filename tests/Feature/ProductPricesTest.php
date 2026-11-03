<?php

declare(strict_types=1);

use App\Actions\ProductPrice\AddProductPriceAction;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Validation\ValidationException;

it('requires authentication', function (): void {
    $this->getJson(route('products.prices.index', 1))->assertUnauthorized();
    $this->postJson(route('products.prices.store', 1))->assertUnauthorized();
});

it('lists the prices of a product with their currency', function (): void {
    actingAsViewer();
    $product = Product::factory()->create();
    ProductPrice::factory()->count(2)->create(['product_id' => $product->id]);

    $this->getJson(route('products.prices.index', $product))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'product_id', 'currency_id', 'currency' => ['id', 'code'], 'price']]])
        ->assertJsonMissingPath('meta');
});

it('returns 404 for the prices of an unknown or soft-deleted product', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $product->delete();

    $this->getJson(route('products.prices.index', 999999))->assertNotFound();
    $this->getJson(route('products.prices.index', $product))->assertNotFound();
    $this->postJson(route('products.prices.store', $product), ['currency_id' => 1, 'price' => '1'])->assertNotFound();
});

it('adds a price to a product', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $currency = Currency::factory()->create();

    $this->postJson(route('products.prices.store', $product), ['currency_id' => $currency->id, 'price' => '1500.5'])
        ->assertCreated()
        ->assertJsonPath('data.product_id', $product->id)
        ->assertJsonPath('data.currency.code', $currency->code)
        ->assertJsonPath('data.price', '1500.50');

    expect($product->prices()->count())->toBe(1);
});

it('forbids a viewer from adding a price', function (): void {
    actingAsViewer();
    $product = Product::factory()->create();
    $currency = Currency::factory()->create();

    $this->postJson(route('products.prices.store', $product), ['currency_id' => $currency->id, 'price' => '10'])
        ->assertForbidden();
});

it('rejects a price in the product base currency', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->postJson(route('products.prices.store', $product), ['currency_id' => $product->currency_id, 'price' => '10'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_id');
});

it('rejects a second price in the same currency', function (): void {
    actingAsAdmin();
    $price = ProductPrice::factory()->create();

    $this->postJson(route('products.prices.store', $price->product), ['currency_id' => $price->currency_id, 'price' => '10'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_id');
});

it('allows the same currency on different products', function (): void {
    actingAsAdmin();
    $price = ProductPrice::factory()->create();
    $other = Product::factory()->create();

    $this->postJson(route('products.prices.store', $other), ['currency_id' => $price->currency_id, 'price' => '10'])
        ->assertCreated();
});

it('validates the price payload', function (array $payload, string $field): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $currency = Currency::factory()->create();
    $payload = array_merge(['currency_id' => $currency->id, 'price' => '10.00'], $payload);

    $this->postJson(route('products.prices.store', $product), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing currency' => [['currency_id' => null], 'currency_id'],
    'unknown currency' => [['currency_id' => 99999], 'currency_id'],
    'missing price' => [['price' => null], 'price'],
    'negative price' => [['price' => '-1'], 'price'],
    'too many decimals' => [['price' => '1.234'], 'price'],
    'not numeric' => [['price' => 'abc'], 'price'],
]);

it('rejects a base-currency price when the currency id is a numeric string', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->postJson(route('products.prices.store', $product), ['currency_id' => (string) $product->currency_id, 'price' => '10'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_id');
});

it('refuses to add a price in the base currency of the product', function (): void {
    $product = Product::factory()->create();

    expect(fn () => app(AddProductPriceAction::class)->handle($product, [
        'currency_id' => $product->currency_id,
        'price' => '1.00',
    ]))->toThrow(ValidationException::class);

    expect(ProductPrice::query()->count())->toBe(0);
});

it('keeps full precision for a price sent as a JSON number', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $currency = Currency::factory()->create();

    $this->postJson(route('products.prices.store', $product), ['currency_id' => $currency->id, 'price' => 1234567890123.45])
        ->assertCreated()
        ->assertJsonPath('data.price', '1234567890123.45');
});

it('maps a concurrent duplicate price to a validation error instead of a server error', function (): void {
    $price = ProductPrice::factory()->create();

    expect(fn () => app(AddProductPriceAction::class)->handle($price->product, [
        'currency_id' => $price->currency_id,
        'price' => '5.00',
    ]))->toThrow(function (ValidationException $e): void {
        expect($e->errors())->toHaveKey('currency_id');
    });

    expect(ProductPrice::query()->count())->toBe(1);
});
