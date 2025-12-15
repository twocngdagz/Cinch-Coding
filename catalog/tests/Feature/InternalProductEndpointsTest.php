<?php

use Database\Factories\ProductFactory;
use Database\Factories\VariantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function generateHmacHeaders(string $method, string $path, string $body = '[]'): array
{
    $serviceId = 'test-service';
    $timestamp = (string) time();
    $secret = 'test-secret';

    $payload = implode("\n", [
        strtoupper($method),
        '/'.ltrim($path, '/'),
        $body,
        $serviceId,
        $timestamp,
    ]);

    $signature = hash_hmac('sha256', $payload, $secret);

    return [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ];
}

beforeEach(function () {
    config([
        'internal-services.allowed_service_ids' => ['test-service'],
        'internal-services.secret' => 'test-secret',
        'internal-services.timestamp_tolerance' => 300,
    ]);
});

test('validate products returns matched products by product ids', function () {
    $product = ProductFactory::new()->create();
    VariantFactory::new()->count(2)->create(['product_id' => $product->id]);

    $body = json_encode(['product_ids' => [$product->id]]);
    $headers = generateHmacHeaders('POST', '/internal/v1/products/validate', $body);

    $response = $this->withHeaders($headers)
        ->postJson('/internal/v1/products/validate', ['product_ids' => [$product->id]]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id);
});

test('validate products returns matched products by variant ids', function () {
    $product = ProductFactory::new()->create();
    $variant = VariantFactory::new()->create(['product_id' => $product->id]);

    $body = json_encode(['variant_ids' => [$variant->id]]);
    $headers = generateHmacHeaders('POST', '/internal/v1/products/validate', $body);

    $response = $this->withHeaders($headers)
        ->postJson('/internal/v1/products/validate', ['variant_ids' => [$variant->id]]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id);
});

test('validate products returns unique products when matching both product and variant ids', function () {
    $product = ProductFactory::new()->create();
    $variant = VariantFactory::new()->create(['product_id' => $product->id]);

    $body = json_encode(['product_ids' => [$product->id], 'variant_ids' => [$variant->id]]);
    $headers = generateHmacHeaders('POST', '/internal/v1/products/validate', $body);

    $response = $this->withHeaders($headers)
        ->postJson('/internal/v1/products/validate', [
            'product_ids' => [$product->id],
            'variant_ids' => [$variant->id],
        ]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('validate products returns empty when no ids match', function () {
    $body = json_encode(['product_ids' => [99999]]);
    $headers = generateHmacHeaders('POST', '/internal/v1/products/validate', $body);

    $response = $this->withHeaders($headers)
        ->postJson('/internal/v1/products/validate', ['product_ids' => [99999]]);

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

test('get product data returns full product data', function () {
    $product = ProductFactory::new()->create();
    VariantFactory::new()->count(2)->create(['product_id' => $product->id]);

    $path = "/internal/v1/products/{$product->id}";
    $headers = generateHmacHeaders('GET', $path);

    $response = $this->withHeaders($headers)
        ->getJson($path);

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
        ->assertJsonPath('data.id', $product->id);
});

test('get product data returns 404 for non-existent product', function () {
    $path = '/internal/v1/products/99999';
    $headers = generateHmacHeaders('GET', $path);

    $response = $this->withHeaders($headers)
        ->getJson($path);

    $response->assertStatus(404);
});

test('validate-items returns priced line items and total_amount', function () {
    $product = ProductFactory::new()->create();
    $variant = VariantFactory::new()->create([
        'product_id' => $product->id,
        'price' => 10.00,
        'stock' => 50,
    ]);

    $payload = [
        'items' => [
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 3],
        ],
    ];

    $body = json_encode($payload);
    $headers = generateHmacHeaders('POST', '/internal/v1/products/validate-items', $body);

    $response = $this->withHeaders($headers)
        ->postJson('/internal/v1/products/validate-items', $payload);

    $response->assertOk()
        ->assertJsonPath('items.0.product_id', $product->id)
        ->assertJsonPath('items.0.variant_id', $variant->id)
        ->assertJsonPath('items.0.quantity', 3)
        ->assertJsonPath('items.0.unit_price', 10)
        ->assertJsonPath('items.0.total_price', 30)
        ->assertJsonPath('total_amount', 30);
});
