<?php

declare(strict_types=1);

use App\Actions\Product\CreateProductAction;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

function productPayload(Currency $currency, array $overrides = []): array
{
    return array_merge([
        'name' => 'Laptop Pro',
        'description' => 'A very capable laptop.',
        'price' => '1299.99',
        'currency_id' => $currency->id,
        'tax_cost' => '100.50',
        'manufacturing_cost' => 800,
    ], $overrides);
}

it('requires authentication on every product route', function (string $method, string $route, array $params): void {
    $this->json($method, route($route, $params))->assertUnauthorized();
})->with([
    'index' => ['GET', 'products.index', []],
    'show' => ['GET', 'products.show', [1]],
    'store' => ['POST', 'products.store', []],
    'update' => ['PUT', 'products.update', [1]],
    'destroy' => ['DELETE', 'products.destroy', [1]],
]);

it('forbids a viewer from every product write', function (string $method, string $route): void {
    actingAsViewer();
    $product = Product::factory()->create();

    $this->json($method, route($route, $route === 'products.store' ? [] : [$product]), productPayload($product->currency))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
})->with([
    'store' => ['POST', 'products.store'],
    'update' => ['PUT', 'products.update'],
    'patch' => ['PATCH', 'products.update'],
    'destroy' => ['DELETE', 'products.destroy'],
]);

it('creates a product with money serialized as strings', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();

    $response = $this->postJson(route('products.store'), productPayload($currency))
        ->assertCreated()
        ->assertJsonStructure(['data' => [
            'id', 'name', 'description', 'price', 'currency_id',
            'currency' => ['id', 'name', 'code', 'symbol', 'exchange_rate'],
            'tax_cost', 'manufacturing_cost', 'prices', 'created_at', 'updated_at',
        ]])
        ->assertJsonPath('data.price', '1299.99')
        ->assertJsonPath('data.tax_cost', '100.50')
        ->assertJsonPath('data.manufacturing_cost', '800.00')
        ->assertJsonPath('data.prices', [])
        ->assertJsonMissingPath('data.deleted_at');

    expect($response->json('data.price'))->toBeString()
        ->and($response->json('data.created_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('stores amounts exactly without float drift', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();

    $id = $this->postJson(route('products.store'), productPayload($currency, ['price' => '0.10', 'tax_cost' => '0.07']))
        ->assertCreated()->json('data.id');

    expect(Product::query()->findOrFail($id)->price)->toBe('0.1000');
});

it('creates a product with additional prices in one request', function (): void {
    actingAsAdmin();
    [$base, $eur, $gbp] = Currency::factory()->count(3)->create();

    $this->postJson(route('products.store'), productPayload($base, ['prices' => [
        ['currency_id' => $eur->id, 'price' => '1199.00'],
        ['currency_id' => $gbp->id, 'price' => 1049.5],
    ]]))
        ->assertCreated()
        ->assertJsonCount(2, 'data.prices')
        ->assertJsonPath('data.prices.0.currency.code', $eur->code)
        ->assertJsonPath('data.prices.1.price', '1049.50')
        ->assertJsonStructure(['data' => ['prices' => [['id', 'product_id', 'currency_id', 'currency', 'price']]]]);
});

it('validates the product payload', function (array $overrides, string $field): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();

    $this->postJson(route('products.store'), productPayload($currency, $overrides))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors($field);
})->with([
    'missing name' => [['name' => null], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'missing description' => [['description' => null], 'description'],
    'missing price' => [['price' => null], 'price'],
    'negative price' => [['price' => -1], 'price'],
    'price too many decimals' => [['price' => '10.999'], 'price'],
    'price not numeric' => [['price' => 'abc'], 'price'],
    'price too large' => [['price' => '1000000000000000'], 'price'],
    'missing currency' => [['currency_id' => null], 'currency_id'],
    'unknown currency' => [['currency_id' => 99999], 'currency_id'],
    'missing tax cost' => [['tax_cost' => null], 'tax_cost'],
    'negative tax cost' => [['tax_cost' => -0.5], 'tax_cost'],
    'missing manufacturing cost' => [['manufacturing_cost' => null], 'manufacturing_cost'],
    'prices not an array' => [['prices' => 'x'], 'prices'],
    'price entry without currency' => [['prices' => [['price' => '1.00']]], 'prices.0.currency_id'],
    'price entry unknown currency' => [['prices' => [['currency_id' => 99999, 'price' => '1.00']]], 'prices.0.currency_id'],
    'price entry without amount' => [['prices' => [['currency_id' => 99999]]], 'prices.0.price'],
]);

it('rejects additional prices in the base currency or repeated currencies', function (): void {
    actingAsAdmin();
    [$base, $eur] = Currency::factory()->count(2)->create();

    $this->postJson(route('products.store'), productPayload($base, ['prices' => [
        ['currency_id' => $base->id, 'price' => '10.00'],
    ]]))->assertUnprocessable()->assertJsonValidationErrors('prices.0.currency_id');

    $this->postJson(route('products.store'), productPayload($base, ['prices' => [
        ['currency_id' => $eur->id, 'price' => '10.00'],
        ['currency_id' => $eur->id, 'price' => '11.00'],
    ]]))->assertUnprocessable()->assertJsonValidationErrors('prices.0.currency_id');

    expect(Product::query()->count())->toBe(0);
});

it('persists nothing when an invalid nested price is sent', function (): void {
    actingAsAdmin();
    $currency = Currency::factory()->create();

    $this->postJson(route('products.store'), productPayload($currency, ['prices' => [
        ['currency_id' => 99999, 'price' => '1.00'],
    ]]))->assertUnprocessable();

    expect(Product::query()->count())->toBe(0)->and(ProductPrice::query()->count())->toBe(0);
});

it('rolls back the product when persisting its prices fails', function (): void {
    $currency = Currency::factory()->create();
    $other = Currency::factory()->create();

    expect(fn () => app(CreateProductAction::class)->handle([
        'name' => 'Broken',
        'description' => 'x',
        'price' => '1.00',
        'currency_id' => $currency->id,
        'tax_cost' => '0',
        'manufacturing_cost' => '0',
        'prices' => [
            ['currency_id' => $other->id, 'price' => '1.00'],
            ['currency_id' => $other->id, 'price' => '2.00'],
        ],
    ]))->toThrow(QueryException::class);

    expect(Product::query()->count())->toBe(0)->and(ProductPrice::query()->count())->toBe(0);
});

it('shows a product with its currency and prices', function (): void {
    actingAsViewer();
    $product = Product::factory()->create(['price' => 10]);
    ProductPrice::factory()->create(['product_id' => $product->id, 'price' => 9.5]);

    $this->getJson(route('products.show', $product))
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.price', '10.00')
        ->assertJsonPath('data.currency.id', $product->currency_id)
        ->assertJsonPath('data.prices.0.price', '9.50')
        ->assertJsonMissingPath('data.deleted_at');
});

it('returns 404 for unknown, non numeric and soft-deleted products', function (): void {
    actingAsAdmin();
    $deleted = Product::factory()->create();
    $deleted->delete();

    $this->getJson(route('products.show', 999999))->assertNotFound()->assertJsonPath('code', 'not_found');
    $this->getJson(route('products.show', $deleted))->assertNotFound();
    $this->putJson(route('products.update', $deleted), productPayload($deleted->currency))->assertNotFound();
    $this->deleteJson(route('products.destroy', $deleted))->assertNotFound();
    $this->getJson('/'.config('app.api_prefix').'/products/abc')->assertNotFound();
});

it('replaces a product fully with PUT', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $currency = Currency::factory()->create();

    $this->putJson(route('products.update', $product), productPayload($currency, ['name' => 'Renamed']))
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.currency_id', $currency->id)
        ->assertJsonPath('data.price', '1299.99');
});

it('requires every field on PUT but not on PATCH', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create(['name' => 'Original', 'price' => 50]);

    $this->putJson(route('products.update', $product), ['name' => 'Only name'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']);

    $this->patchJson(route('products.update', $product), ['price' => '75.25'])
        ->assertOk()
        ->assertJsonPath('data.price', '75.25')
        ->assertJsonPath('data.name', 'Original');
});

it('still validates fields sent in a PATCH', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->patchJson(route('products.update', $product), ['price' => -5, 'name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price', 'name']);
});

it('rejects changing the base currency to one that already has a price', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $price = ProductPrice::factory()->create(['product_id' => $product->id]);

    $this->patchJson(route('products.update', $product), ['currency_id' => $price->currency_id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_id');

    $free = Currency::factory()->create();
    $this->patchJson(route('products.update', $product), ['currency_id' => $free->id])->assertOk();
});

it('soft deletes a product', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->deleteJson(route('products.destroy', $product))->assertNoContent();

    expect(Product::query()->find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
    $this->getJson(route('products.show', $product))->assertNotFound();
});

it('lists products paginated with links and meta', function (): void {
    actingAsViewer();
    Product::factory()->count(20)->create();

    $this->getJson(route('products.index'))
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'price', 'currency', 'prices']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    $this->getJson(route('products.index', ['per_page' => 5, 'page' => 4]))
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 4);
});

it('excludes soft-deleted products from the listing', function (): void {
    actingAsViewer();
    Product::factory()->count(2)->create();
    Product::factory()->create()->delete();

    $this->getJson(route('products.index'))->assertJsonPath('meta.total', 2);
});

it('filters products by search text case-insensitively', function (): void {
    actingAsViewer();
    Product::factory()->create(['name' => 'Gaming Laptop']);
    Product::factory()->create(['name' => 'Office Chair', 'description' => 'Ergonomic LAPTOP stand']);
    Product::factory()->create(['name' => 'Desk Lamp', 'description' => 'Bright']);

    $this->getJson(route('products.index', ['search' => 'laptop']))
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('filters products by currency and price range', function (): void {
    actingAsViewer();
    $usd = Currency::factory()->create();
    $eur = Currency::factory()->create();
    Product::factory()->create(['currency_id' => $usd->id, 'price' => 10]);
    Product::factory()->create(['currency_id' => $usd->id, 'price' => 50]);
    Product::factory()->create(['currency_id' => $eur->id, 'price' => 50]);

    $this->getJson(route('products.index', ['currency_id' => $usd->id]))->assertJsonPath('meta.total', 2);
    $this->getJson(route('products.index', ['min_price' => '20', 'max_price' => '60']))->assertJsonPath('meta.total', 2);
    $this->getJson(route('products.index', ['currency_id' => $eur->id, 'min_price' => '60']))->assertJsonPath('meta.total', 0);
});

it('filters products by max_price without min_price', function (): void {
    actingAsViewer();
    Product::factory()->create(['price' => 10]);
    Product::factory()->create(['price' => 50]);

    $this->getJson(route('products.index', ['max_price' => '20']))->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson(route('products.index', ['min_price' => '', 'max_price' => '20']))->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson(route('products.index', ['min_price' => '30', 'max_price' => '20']))->assertUnprocessable();
});

it('sorts products', function (string $sort, array $expected): void {
    actingAsViewer();
    $this->travelTo(now()->subDays(3));
    Product::factory()->create(['name' => 'B item', 'price' => 20]);
    $this->travelTo(now()->addDay());
    Product::factory()->create(['name' => 'A item', 'price' => 30]);
    $this->travelTo(now()->addDay());
    Product::factory()->create(['name' => 'C item', 'price' => 10]);

    $names = $this->getJson(route('products.index', ['sort' => $sort]))->assertOk()->json('data.*.name');

    expect($names)->toBe($expected);
})->with([
    'name asc' => ['name', ['A item', 'B item', 'C item']],
    'name desc' => ['-name', ['C item', 'B item', 'A item']],
    'price asc' => ['price', ['C item', 'B item', 'A item']],
    'price desc' => ['-price', ['A item', 'B item', 'C item']],
    'created asc' => ['created_at', ['B item', 'A item', 'C item']],
    'created desc' => ['-created_at', ['C item', 'A item', 'B item']],
]);

it('rejects invalid listing parameters', function (array $query, string $field): void {
    actingAsViewer();

    $this->getJson(route('products.index', $query))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors($field);
})->with([
    'invalid sort' => [['sort' => 'description'], 'sort'],
    'injection in sort' => [['sort' => 'price;drop table products'], 'sort'],
    'per_page too large' => [['per_page' => 101], 'per_page'],
    'per_page zero' => [['per_page' => 0], 'per_page'],
    'unknown currency' => [['currency_id' => 99999], 'currency_id'],
    'search too long' => [['search' => str_repeat('a', 101)], 'search'],
    'bad min price' => [['min_price' => 'abc'], 'min_price'],
    'max below min' => [['min_price' => '50', 'max_price' => '10'], 'max_price'],
    'page zero' => [['page' => 0], 'page'],
]);

it('does not run extra queries as the number of products grows', function (): void {
    actingAsViewer();
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('products.index', ['per_page' => 100]))->assertOk();

        return count(DB::getQueryLog());
    };

    // Warm-up request: loads the permission cache so it is not counted below.
    $this->getJson(route('products.index'))->assertOk();

    Product::factory()->count(5)->create()->each(
        fn (Product $product) => ProductPrice::factory()->count(2)->create(['product_id' => $product->id]),
    );
    $few = $countQueries();

    Product::factory()->count(15)->create()->each(
        fn (Product $product) => ProductPrice::factory()->count(2)->create(['product_id' => $product->id]),
    );
    $many = $countQueries();

    expect($many)->toBe($few);
});

it('rejects base-currency prices and repeated currencies sent as numeric strings', function (): void {
    actingAsAdmin();
    [$base, $eur] = Currency::factory()->count(2)->create();

    $this->postJson(route('products.store'), productPayload($base, ['prices' => [
        ['currency_id' => (string) $base->id, 'price' => '10.00'],
    ]]))->assertUnprocessable()->assertJsonValidationErrors('prices.0.currency_id');

    $this->postJson(route('products.store'), productPayload($base, ['prices' => [
        ['currency_id' => (string) $eur->id, 'price' => '10.00'],
        ['currency_id' => $eur->id, 'price' => '11.00'],
    ]]))->assertUnprocessable()->assertJsonValidationErrors('prices.0.currency_id');

    expect(Product::query()->count())->toBe(0);
});

it('rejects a base currency change to one with a price when the id is a string', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create();
    $price = ProductPrice::factory()->create(['product_id' => $product->id]);

    $this->patchJson(route('products.update', $product), ['currency_id' => (string) $price->currency_id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_id');
});

it('refuses to create a product with a price in its base currency', function (): void {
    $currency = Currency::factory()->create();

    expect(fn () => app(CreateProductAction::class)->handle([
        'name' => 'Broken',
        'description' => 'x',
        'price' => '1.00',
        'currency_id' => (string) $currency->id,
        'tax_cost' => '0',
        'manufacturing_cost' => '0',
        'prices' => [['currency_id' => $currency->id, 'price' => '1.00']],
    ]))->toThrow(ValidationException::class);

    expect(Product::query()->count())->toBe(0);
});
