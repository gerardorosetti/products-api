<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\Concerns\NormalizesDecimalInput;
use App\Models\Product;
use App\Support\Rules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateProductRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['price', 'tax_cost', 'manufacturing_cost']);

        if ($this->has('currency_id')) {
            $this->merge(['currency_id' => Rules::castId($this->input('currency_id'))]);
        }
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [Rules::requireAnyField($this, ['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost'])];
    }

    /**
     * PUT replaces the whole resource (all fields required); PATCH accepts any subset.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return Rules::partial([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => Rules::amount(),
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id'),
                static function (string $attribute, mixed $value, Closure $fail) use ($product): void {
                    if ($product instanceof Product && $product->prices()->where('currency_id', $value)->exists()) {
                        $fail('The base currency cannot be one that already has an additional price for this product.');
                    }
                },
            ],
            'tax_cost' => Rules::amount(),
            'manufacturing_cost' => Rules::amount(),
        ], $this->isMethod('PATCH'));
    }
}
