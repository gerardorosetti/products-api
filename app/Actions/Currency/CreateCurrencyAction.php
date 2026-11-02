<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;

final readonly class CreateCurrencyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Currency
    {
        return Currency::query()->create($data);
    }
}
