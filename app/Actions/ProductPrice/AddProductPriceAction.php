<?php

declare(strict_types=1);

namespace App\Actions\ProductPrice;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AddProductPriceAction
{
    /**
     * @param  array{currency_id: int, price: string}  $data
     */
    public function handle(Product $product, array $data): ProductPrice
    {
        if ((int) $data['currency_id'] === $product->currency_id) {
            throw ValidationException::withMessages([
                'currency_id' => 'The price currency cannot be the product base currency.',
            ]);
        }

        try {
            // The nested transaction keeps an enclosing PostgreSQL transaction usable after a failed insert.
            $price = DB::transaction(fn (): ProductPrice => $product->prices()->create($data));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'currency_id' => 'This product already has a price in that currency.',
            ]);
        }

        return $price->load('currency');
    }
}
