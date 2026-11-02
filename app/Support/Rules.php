<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validation rule sets shared by several form requests.
 */
final class Rules
{
    /**
     * Monetary amount with at most two decimals (numeric strings are accepted).
     *
     * @return list<string>
     */
    public static function amount(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'decimal:0,2', 'min:0', 'max:999999999999999.99'];
    }

    /**
     * Exchange rate with at most eight decimals.
     *
     * @return list<string>
     */
    public static function exchangeRate(): array
    {
        return ['required', 'decimal:0,8', 'gt:0', 'max:9999999999'];
    }

    /**
     * Prepends "sometimes" so PATCH requests may omit the field.
     *
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, list<mixed>>
     */
    public static function partial(array $rules, bool $isPatch): array
    {
        if (! $isPatch) {
            return $rules;
        }

        return array_map(static fn (array $fieldRules): array => ['sometimes', ...$fieldRules], $rules);
    }
}
