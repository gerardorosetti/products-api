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
