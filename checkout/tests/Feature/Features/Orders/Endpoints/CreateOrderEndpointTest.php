<?php

declare(strict_types=1);

use App\Features\Orders\Controllers\CreateOrderController;
use App\Models\Order;
use App\Services\InternalHttpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::post('/api/orders', CreateOrderController::class);

    $this->app->singleton(InternalHttpClient::class, function () {
        return new InternalHttpClient(
            'http://catalog.test',
            'test-secret',
            'checkout'
        );
    });
});

it('creates an order successfully with valid data', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response([
            'items' => [
                [
                    'product_id' => 1,
                    'variant_id' => 10,
                    'quantity' => 2,
                    'price' => 1500,
                    'name' => 'Test Product',
                    'sku' => 'TP-001',
                ],
                [
                    'product_id' => 2,
                    'variant_id' => 20,
                    'quantity' => 1,
                    'price' => 2500,
                    'name' => 'Another Product',
                    'sku' => 'AP-001',
                ],
            ],
        ]),
    ]);

    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2],
            ['product_id' => 2, 'variant_id' => 20, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseCount('orders', 1);

    $order = Order::first();
    expect($order->email)->toBe('customer@example.com');
    expect($order->items)->toBeArray();
    expect($order->items)->toHaveCount(2);
    expect($order->items[0])->toMatchArray([
        'product_id' => 1,
        'variant_id' => 10,
        'quantity' => 2,
        'price' => 1500,
        'name' => 'Test Product',
        'sku' => 'TP-001',
    ]);
    expect($order->items[1])->toMatchArray([
        'product_id' => 2,
        'variant_id' => 20,
        'quantity' => 1,
        'price' => 2500,
        'name' => 'Another Product',
        'sku' => 'AP-001',
    ]);
    expect((int) $order->total_amount)->toBe(5500);

    $response->assertJsonStructure([
        'id',
        'email',
        'items',
        'total_amount',
        'created_at',
        'updated_at',
    ]);
    $response->assertJsonPath('email', 'customer@example.com');
});

it('returns 422 when email is missing', function (): void {
    $response = $this->postJson('/api/orders', [
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when email is invalid', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'not-an-email',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when items is missing', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when items is empty', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when item is missing product_id', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['variant_id' => 10, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items.0.product_id']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when item is missing variant_id', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items.0.variant_id']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when item is missing quantity', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items.0.quantity']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 422 when quantity is less than 1', function (): void {
    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 0],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items.0.quantity']);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 500 when catalog service is unavailable', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response(['error' => 'Service unavailable'], 500),
    ]);

    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(500);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 500 when catalog validation fails with 400', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response(['error' => 'Invalid product'], 400),
    ]);

    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 999, 'variant_id' => 999, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(500);

    $this->assertDatabaseCount('orders', 0);
});

it('returns 500 when catalog validation fails with 422', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response(['error' => 'Validation failed'], 422),
    ]);

    $response = $this->postJson('/api/orders', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(500);

    $this->assertDatabaseCount('orders', 0);
});
