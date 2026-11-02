<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Exceptions\CurrencyInUseException;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;

final readonly class DeleteCurrencyAction
{
    /**
     * @throws CurrencyInUseException when a product (including soft-deleted ones) or a price references the currency
     */
    public function handle(Currency $currency): void
    {
        $inUse = Product::withTrashed()->where('currency_id', $currency->id)->exists()
            || ProductPrice::query()->where('currency_id', $currency->id)->exists();

        if ($inUse) {
            throw new CurrencyInUseException;
        }

        $currency->delete();
    }
}
