<?php

declare(strict_types=1);

use App\Features\Orders\Jobs\SendOrderEmailJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

function generateHmacSignature(string $method, string $path, string $body, string $serviceId, string $timestamp, string $secret): string
{
    $payload = implode("\n", [
        strtoupper($method),
        '/'.ltrim($path, '/'),
        $body,
        $serviceId,
        $timestamp,
    ]);

    return hash_hmac('sha256', $payload, $secret);
}

function validOrderPayload(): array
{
    return [
        'email' => 'customer@example.com',
        'items' => [
            [
                'product_id' => 1,
                'variant_id' => 10,
                'quantity' => 2,
                'unit_price' => 25.00,
                'total_price' => 50.00,
            ],
        ],
        'total_amount' => 50.00,
    ];
}

beforeEach(function (): void {
    Config::set('internal-services.allowed_service_ids', ['checkout']);
    Config::set('internal-services.secret', 'test-secret');
    Config::set('internal-services.timestamp_tolerance', 300);
});

test('rejects missing HMAC headers', function (): void {
    $response = $this->postJson('/internal/orders/receive', validOrderPayload());

    $response->assertStatus(401);
    $response->assertJson([
        'error' => 'Unauthorized',
        'message' => 'Missing required authentication headers.',
    ]);
});

test('rejects invalid signature', function (): void {
    $timestamp = (string) time();
    $response = $this->postJson('/internal/orders/receive', validOrderPayload(), [
        'x-service-id' => 'checkout',
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => 'invalid-signature',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'error' => 'Unauthorized',
        'message' => 'Invalid signature.',
    ]);
});

test('accepts valid request with valid signature', function (): void {
    Queue::fake();

    $payload = validOrderPayload();
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/orders/receive';

    $signature = generateHmacSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/orders/receive', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ]);

    $response->assertStatus(202);
    $response->assertJson(['status' => 'accepted']);
});

test('dispatches SendOrderEmailJob with correct payload', function (): void {
    Queue::fake();

    $payload = validOrderPayload();
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/orders/receive';

    $signature = generateHmacSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/orders/receive', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ]);

    $response->assertStatus(202);

    Queue::assertPushed(SendOrderEmailJob::class);
});

test('validation errors return 422', function (): void {
    Queue::fake();

    $payload = ['email' => 'not-an-email'];
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/orders/receive';

    $signature = generateHmacSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/orders/receive', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items', 'total_amount']);
});
