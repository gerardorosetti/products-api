<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const PRODUCT_COUNT = 30;

    /**
     * Creates demo products, each with 1 to 3 prices in currencies other than its base one.
     * Skipped when products already exist so re-seeding does not duplicate data.
     */
    public function run(): void
    {
        if (Product::withTrashed()->exists()) {
            return;
        }

        $currencies = Currency::query()->get();

        if ($currencies->count() < 2) {
            $this->call(CurrencySeeder::class);
            $currencies = Currency::query()->get();
        }

        for ($i = 0; $i < self::PRODUCT_COUNT; $i++) {
            $base = $currencies->random();

            $product = Product::factory()->create(['currency_id' => $base->id]);

            $others = $currencies->where('id', '!=', $base->id)->random(fake()->numberBetween(1, 3));

            foreach ($others as $currency) {
                ProductPrice::factory()->create([
                    'product_id' => $product->id,
                    'currency_id' => $currency->id,
                ]);
            }
        }
    }
}
