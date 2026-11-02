<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;

final readonly class UpdateCurrencyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Currency $currency, array $data): Currency
    {
        $currency->update($data);

        return $currency->refresh();
    }
}
