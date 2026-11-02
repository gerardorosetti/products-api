<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;

final readonly class UpdateProductAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh()->load(['currency', 'prices.currency']);
    }
}
