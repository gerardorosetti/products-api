<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $prices = $this->input('prices');

        if (is_array($prices)) {
            $prices = array_map(
                static fn (mixed $entry): mixed => is_array($entry) && array_key_exists('currency_id', $entry)
                    ? [...$entry, 'currency_id' => Rules::castId($entry['currency_id'])]
                    : $entry,
                $prices,
            );
        }

        $this->merge(array_filter([
            'currency_id' => Rules::castId($this->input('currency_id')),
            'prices' => $prices,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => Rules::amount(),
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'tax_cost' => Rules::amount(),
            'manufacturing_cost' => Rules::amount(),
            'prices' => ['nullable', 'array', 'max:50'],
            'prices.*.currency_id' => ['required', 'integer', 'distinct', Rule::exists('currencies', 'id'), 'different:currency_id'],
            'prices.*.price' => Rules::amount(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prices.*.currency_id.distinct' => 'Each currency can only appear once in the prices list.',
            'prices.*.currency_id.different' => 'An additional price cannot use the product base currency.',
        ];
    }
}
