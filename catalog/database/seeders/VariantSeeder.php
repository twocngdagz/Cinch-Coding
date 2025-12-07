<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class VariantSeeder extends Seeder
{
    public function run(): void
    {
        Product::all()->each(function (Product $product) {
            Variant::factory()->count(3)->create([
                'product_id' => $product->id,
            ]);
        });
    }
}
