<?php

declare(strict_types=1);

use App\Features\Orders\Requests\ValidateOrderRequest;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::post('/test-validate-order', fn (ValidateOrderRequest $request) => response()->json(['success' => true]));
});

it('fails when email is missing', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'items' => [
            ['product_id' => 1, 'variant_id' => 1, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails when email is not a valid email', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'invalid-email',
        'items' => [
            ['product_id' => 1, 'variant_id' => 1, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails when items is missing', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('fails when items is not an array', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
        'items' => 'not-an-array',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('fails when an item is missing product_id', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
        'items' => [
            ['variant_id' => 1, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.product_id']);
});

it('fails when an item is missing variant_id', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.variant_id']);
});

it('fails when an item is missing quantity', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 1],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity']);
});

it('fails when quantity is less than 1', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 1, 'quantity' => 0],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity']);
});

it('passes when all fields are valid', function (): void {
    $response = $this->postJson('/test-validate-order', [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 1, 'quantity' => 1],
            ['product_id' => 2, 'variant_id' => 3, 'quantity' => 5],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});
