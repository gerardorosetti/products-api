<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Illustrative exchange rates relative to USD; not live market data.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.92000000'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => '0.79000000'],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$', 'exchange_rate' => '17.10000000'],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$', 'exchange_rate' => '3900.00000000'],
            ['code' => 'VES', 'name' => 'Venezuelan Bolivar', 'symbol' => 'Bs', 'exchange_rate' => '36.50000000'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'exchange_rate' => '151.00000000'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$', 'exchange_rate' => '5.00000000'],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
