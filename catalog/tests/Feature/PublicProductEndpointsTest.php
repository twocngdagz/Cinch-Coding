<?php

use Database\Factories\ProductFactory;
use Database\Factories\VariantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('get products returns a list of products with variants', function () {
    $product = ProductFactory::new()->create();
    VariantFactory::new()->count(2)->create(['product_id' => $product->id]);

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'status',
                    'variants' => [
                        '*' => [
                            'id',
                            'sku',
                            'price',
                            'compare_at_price',
                            'options',
                            'stock',
                        ],
                    ],
                ],
            ],
        ]);
});

test('get products returns empty list when no products exist', function () {
    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

test('get single product returns product with variants', function () {
    $product = ProductFactory::new()->create();
    VariantFactory::new()->count(3)->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'slug',
                'description',
                'status',
                'variants' => [
                    '*' => [
                        'id',
                        'sku',
                        'price',
                        'compare_at_price',
                        'options',
                        'stock',
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.title', $product->title);
});

test('get single product returns 404 for non-existent product', function () {
    $response = $this->getJson('/api/v1/products/99999');

    $response->assertStatus(404);
});

test('get products returns multiple products', function () {
    ProductFactory::new()->count(5)->create()->each(function ($product) {
        VariantFactory::new()->count(2)->create(['product_id' => $product->id]);
    });

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

