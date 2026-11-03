<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $price
 * @property int $currency_id
 * @property string $tax_cost
 * @property string $manufacturing_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Currency $currency
 * @property-read Collection<int, ProductPrice> $prices
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    public const DEFAULT_SORT = '-created_at';

    /** Allowed sort keys mapped to their column. */
    private const SORT_COLUMNS = [
        'name' => 'name',
        'price' => 'price',
        'created_at' => 'created_at',
    ];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'price',
        'currency_id',
        'tax_cost',
        'manufacturing_cost',
    ];

    /**
     * @return list<string>
     */
    public static function sortOptions(): array
    {
        $options = [];

        foreach (array_keys(self::SORT_COLUMNS) as $key) {
            $options[] = $key;
            $options[] = '-'.$key;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'tax_cost' => 'decimal:4',
            'manufacturing_cost' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->orderBy('product_prices.id');
    }

    /**
     * Applies the listing filters and sorting.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters  search, currency_id, min_price, max_price, sort
     * @return Builder<Product>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $search = $filters['search'] ?? null;
        $currencyId = $filters['currency_id'] ?? null;
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $sort = is_string($filters['sort'] ?? null) ? $filters['sort'] : self::DEFAULT_SORT;

        $query
            ->when(is_string($search) && $search !== '', function (Builder $q) use ($search): void {
                // Explicit ESCAPE keeps % and _ literal on every driver (SQLite has no default escape character).
                // "!" instead of a backslash: PDO's SQL parser on PHP 8.3 reads '\' as an escaped quote
                // and then fails to bind the placeholders that follow it.
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';

                $q->where(
                    fn (Builder $q) => $q
                        ->whereRaw("lower(name) like lower(?) escape '!'", [$pattern])
                        ->orWhereRaw("lower(description) like lower(?) escape '!'", [$pattern]),
                );
            })
            ->when($currencyId !== null, fn (Builder $q) => $q->where('currency_id', $currencyId))
            ->when($minPrice !== null, fn (Builder $q) => $q->where('price', '>=', $minPrice))
            ->when($maxPrice !== null, fn (Builder $q) => $q->where('price', '<=', $maxPrice));

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = self::SORT_COLUMNS[ltrim($sort, '-')] ?? self::SORT_COLUMNS['created_at'];

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}
