<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;

final readonly class DeleteProductAction
{
    /**
     * Soft-deletes the product.
     */
    public function handle(Product $product): void
    {
        $product->delete();
    }
}
