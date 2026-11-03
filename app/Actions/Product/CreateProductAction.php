<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateProductAction
{
    /**
     * Creates the product and its optional additional prices atomically.
     *
     * @param  array<string, mixed>  $data  product attributes plus an optional "prices" list of {currency_id, price}
     */
    public function handle(array $data): Product
    {
        /** @var list<array{currency_id: int, price: string}> $prices */
        $prices = $data['prices'] ?? [];
        unset($data['prices']);

        foreach ($prices as $index => $price) {
            if ((int) $price['currency_id'] === (int) $data['currency_id']) {
                throw ValidationException::withMessages([
                    "prices.{$index}.currency_id" => 'An additional price cannot use the product base currency.',
                ]);
            }
        }

        return DB::transaction(function () use ($data, $prices): Product {
            $product = Product::query()->create($data);

            if ($prices !== []) {
                $product->prices()->createMany($prices);
            }

            return $product->load(['currency', 'prices.currency']);
        });
    }
}
