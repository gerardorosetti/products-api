<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateCurrencyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Currency $currency, array $data): Currency
    {
        try {
            DB::transaction(fn (): bool => $currency->update($data));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'A currency with this code already exists.']);
        }

        return $currency->refresh();
    }
}
