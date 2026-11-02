<?php

declare(strict_types=1);

namespace App\Actions\ProductPrice;

use App\Models\Product;
use App\Models\ProductPrice;

final readonly class AddProductPriceAction
{
    /**
     * @param  array{currency_id: int, price: string}  $data
     */
    public function handle(Product $product, array $data): ProductPrice
    {
        $price = $product->prices()->create($data);

        return $price->load('currency');
    }
}
