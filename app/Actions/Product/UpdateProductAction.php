<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateProductAction
{
    /**
     * Locks the product row so a concurrent price insert cannot slip in between the
     * base currency check and the update.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            Product::query()->whereKey($product->id)->lockForUpdate()->first();

            if (
                isset($data['currency_id'])
                && $product->prices()->where('currency_id', $data['currency_id'])->exists()
            ) {
                throw ValidationException::withMessages([
                    'currency_id' => 'The base currency cannot be one that already has an additional price for this product.',
                ]);
            }

            $product->update($data);

            return $product->refresh()->load(['currency', 'prices.currency']);
        });
    }
}
