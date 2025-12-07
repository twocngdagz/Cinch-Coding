<?php

namespace Database\Factories;

use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Variant>
 */
class VariantFactory extends Factory
{
    protected $model = Variant::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'product_id' => ProductFactory::new(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'price' => $price,
            'compare_at_price' => fake()->optional(0.3)->randomFloat(2, $price + 10, $price + 100),
            'options' => [
                'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
                'color' => fake()->safeColorName(),
            ],
            'stock' => fake()->numberBetween(0, 100),
        ];
    }
}
