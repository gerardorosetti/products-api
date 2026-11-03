<?php

declare(strict_types=1);

namespace App\Http\Requests\Currency;

use App\Http\Requests\Concerns\NormalizesDecimalInput;
use App\Models\Currency;
use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCurrencyRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Currency::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['exchange_rate']);

        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[A-Z]{3}$/', Rule::unique('currencies', 'code')],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => Rules::exchangeRate(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'The code must be a three-letter ISO 4217 code.',
            'code.unique' => 'A currency with this code already exists.',
        ];
    }
}
