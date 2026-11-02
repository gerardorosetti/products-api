<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => rtrim(fake()->unique()->sentence(3), '.'),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 1, 5000),
            'currency_id' => Currency::factory(),
            'tax_cost' => fake()->randomFloat(2, 0, 500),
            'manufacturing_cost' => fake()->randomFloat(2, 0, 1000),
        ];
    }
}
