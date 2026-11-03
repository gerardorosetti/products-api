<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

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
     * Validator callback: a PATCH must send at least one of the updatable fields.
     *
     * @param  list<string>  $fields
     * @return Closure(Validator): void
     */
    public static function requireAnyField(Request $request, array $fields): Closure
    {
        return static function (Validator $validator) use ($request, $fields): void {
            if ($request->isMethod('PATCH') && ! $request->hasAny($fields)) {
                $validator->errors()->add('fields', 'At least one of the following fields is required: '.implode(', ', $fields).'.');
            }
        };
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

    /**
     * Casts integer-like strings ("1") to int so id comparisons between rules are type-safe.
     */
    public static function castId(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[0-9]{1,18}$/', $value) === 1 ? (int) $value : $value;
    }
}
