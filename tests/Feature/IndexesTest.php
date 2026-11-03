<?php

declare(strict_types=1);

use App\Models\Product;
use Illuminate\Support\Facades\Schema;

it('indexes foreign keys and product sort columns', function (string $table, string $column): void {
    expect(Schema::hasIndex($table, [$column]))->toBeTrue();
})->with([
    'products.currency_id' => ['products', 'currency_id'],
    'products.price' => ['products', 'price'],
    'products.created_at' => ['products', 'created_at'],
    'product_prices.currency_id' => ['product_prices', 'currency_id'],
]);

it('returns the prices of a product ordered by id', function (): void {
    $orders = (new Product)->prices()->getQuery()->getQuery()->orders;

    expect($orders)->toBe([['column' => 'product_prices.id', 'direction' => 'asc']]);
});
