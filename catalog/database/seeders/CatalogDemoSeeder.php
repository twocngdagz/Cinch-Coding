<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory()
            ->count(25)
            ->create()
            ->each(function (Product $product) {
                Variant::factory()
                    ->count(fake()->numberBetween(3, 8))
                    ->create(['product_id' => $product->id]);
            });
    }
}

