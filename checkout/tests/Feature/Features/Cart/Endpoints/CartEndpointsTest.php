<?php

declare(strict_types=1);

use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an empty cart when token is missing', function (): void {
    $response = $this->getJson('/api/v1/cart');

    $response->assertOk()
        ->assertHeader('X-Cart-Token');

    $token = $response->json('cart_token');
    expect($token)->toBeString()->not->toBeEmpty();

    $response->assertJsonPath('cart_token', $token)
        ->assertJsonPath('items', []);

    $this->assertDatabaseCount('carts', 1);
    $this->assertDatabaseCount('cart_items', 0);
});

it('creates a token and adds an item', function (): void {
    $response = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 10,
        'quantity' => 2,
    ]);

    $response->assertOk()
        ->assertHeader('X-Cart-Token')
        ->assertJsonPath('items.0.variant_id', 10)
        ->assertJsonPath('items.0.quantity', 2);

    $token = $response->json('cart_token');
    expect($token)->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('carts', ['token' => $token]);
    $this->assertDatabaseHas('cart_items', [
        'variant_id' => 10,
        'quantity' => 2,
    ]);
});

it('increments quantity when the same variant is added', function (): void {
    $firstResponse = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 10,
        'quantity' => 1,
    ]);

    $token = $firstResponse->json('cart_token');

    $response = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 10,
        'quantity' => 2,
    ], [
        'X-Cart-Token' => $token,
    ]);

    $response->assertOk()
        ->assertJsonPath('items.0.variant_id', 10)
        ->assertJsonPath('items.0.quantity', 3);

    $cart = Cart::where('token', $token)->firstOrFail();
    expect($cart->items()->firstOrFail()->quantity)->toBe(3);
});

it('sets quantity via patch', function (): void {
    $firstResponse = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 12,
        'quantity' => 1,
    ]);

    $token = $firstResponse->json('cart_token');

    $response = $this->patchJson('/api/v1/cart/items/12', [
        'quantity' => 5,
    ], [
        'X-Cart-Token' => $token,
    ]);

    $response->assertOk()
        ->assertJsonPath('items.0.variant_id', 12)
        ->assertJsonPath('items.0.quantity', 5);
});

it('removes an item when quantity is set to zero', function (): void {
    $firstResponse = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 15,
        'quantity' => 1,
    ]);

    $token = $firstResponse->json('cart_token');

    $response = $this->patchJson('/api/v1/cart/items/15', [
        'quantity' => 0,
    ], [
        'X-Cart-Token' => $token,
    ]);

    $response->assertOk()
        ->assertJsonPath('items', []);

    $this->assertDatabaseCount('cart_items', 0);
});

it('removes an item via delete', function (): void {
    $firstResponse = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 20,
        'quantity' => 1,
    ]);

    $token = $firstResponse->json('cart_token');

    $response = $this->deleteJson('/api/v1/cart/items/20', [], [
        'X-Cart-Token' => $token,
    ]);

    $response->assertOk()
        ->assertJsonPath('items', []);

    $this->assertDatabaseCount('cart_items', 0);
});

it('clears the cart but keeps the cart record', function (): void {
    $firstResponse = $this->postJson('/api/v1/cart/items', [
        'variant_id' => 30,
        'quantity' => 1,
    ]);

    $token = $firstResponse->json('cart_token');

    $this->postJson('/api/v1/cart/items', [
        'variant_id' => 31,
        'quantity' => 2,
    ], [
        'X-Cart-Token' => $token,
    ]);

    $response = $this->deleteJson('/api/v1/cart', [], [
        'X-Cart-Token' => $token,
    ]);

    $response->assertOk()
        ->assertJsonPath('items', []);

    $this->assertDatabaseCount('carts', 1);
    $this->assertDatabaseCount('cart_items', 0);
});
