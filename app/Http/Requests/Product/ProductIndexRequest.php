<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Product::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $price = array_values(array_filter(Rules::amount(false), static fn (string $rule): bool => $rule !== 'nullable'));

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'min_price' => ['nullable', ...$price],
            'max_price' => ['nullable', ...$price, ...($this->filled('min_price') ? ['gte:min_price'] : [])],
            'sort' => ['nullable', 'string', Rule::in(Product::sortOptions())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
