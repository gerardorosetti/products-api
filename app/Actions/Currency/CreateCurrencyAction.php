<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateCurrencyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Currency
    {
        try {
            // The nested transaction keeps an enclosing PostgreSQL transaction usable after a failed insert.
            return DB::transaction(fn (): Currency => Currency::query()->create($data));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'A currency with this code already exists.']);
        }
    }
}
