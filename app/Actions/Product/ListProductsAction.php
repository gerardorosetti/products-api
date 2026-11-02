<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListProductsAction
{
    public const DEFAULT_PER_PAGE = 15;

    /**
     * @param  array<string, mixed>  $filters  search, currency_id, min_price, max_price, sort, per_page, page
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(array $filters): LengthAwarePaginator
    {
        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : self::DEFAULT_PER_PAGE;

        return Product::query()
            ->with(['currency', 'prices.currency'])
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString();
    }
}
