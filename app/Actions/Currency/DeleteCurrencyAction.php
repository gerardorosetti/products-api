<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Exceptions\CurrencyInUseException;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Support\ConstraintViolation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCurrencyAction
{
    /**
     * Locks the currency row while checking its usage; the foreign keys remain the last line of defense.
     *
     * @throws CurrencyInUseException when a product (including soft-deleted ones) or a price references the currency
     */
    public function handle(Currency $currency): void
    {
        try {
            DB::transaction(function () use ($currency): void {
                Currency::query()->whereKey($currency->id)->lockForUpdate()->first();

                $inUse = Product::withTrashed()->where('currency_id', $currency->id)->exists()
                    || ProductPrice::query()->where('currency_id', $currency->id)->exists();

                if ($inUse) {
                    throw new CurrencyInUseException;
                }

                $currency->delete();
            });
        } catch (QueryException $e) {
            if (ConstraintViolation::isForeignKey($e)) {
                throw new CurrencyInUseException;
            }

            throw $e;
        }
    }
}
