<?php

declare(strict_types=1);

namespace App\Actions\ProductPrice;

use App\Models\Product;
use App\Models\ProductPrice;
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

        $price = $product->prices()->create($data);

        return $price->load('currency');
    }
}
