<?php

declare(strict_types=1);

use App\Support\Money;

it('formats amounts with two decimals', function (string $input, string $expected): void {
    expect(Money::format($input))->toBe($expected);
})->with([
    'zero' => ['0', '0.00'],
    'integer' => ['5', '5.00'],
    'four decimals' => ['1299.9900', '1299.99'],
    'rounds half up' => ['1.005', '1.01'],
    'rounds down below half' => ['1.0049', '1.00'],
    'rounds up' => ['2.9951', '3.00'],
    'large value' => ['999999999999999.9900', '999999999999999.99'],
    'very large rounding' => ['99999999999999999999.995', '100000000000000000000.00'],
    'negative' => ['-1.005', '-1.01'],
    'leading zeros' => ['0.1000', '0.10'],
]);
