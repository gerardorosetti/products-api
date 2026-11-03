<?php

declare(strict_types=1);

namespace App\Http\Requests\ProductPrice;

use App\Models\Product;
use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('addPrice', $product) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_id')) {
            $this->merge(['currency_id' => Rules::castId($this->input('currency_id'))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : 0;
        $baseCurrencyId = $product instanceof Product ? $product->currency_id : 0;

        return [
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id'),
                Rule::notIn([$baseCurrencyId]),
                Rule::unique('product_prices', 'currency_id')->where('product_id', $productId),
            ],
            'price' => Rules::amount(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency_id.not_in' => 'The price currency cannot be the product base currency.',
            'currency_id.unique' => 'This product already has a price in that currency.',
        ];
    }
}
