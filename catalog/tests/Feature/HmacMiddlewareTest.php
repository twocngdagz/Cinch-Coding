<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function computeHmacSignature(
    string $method,
    string $path,
    string $body,
    string $serviceId,
    string $timestamp,
    string $secret
): string {
    $payload = implode("\n", [
        strtoupper($method),
        '/' . ltrim($path, '/'),
        $body,
        $serviceId,
        $timestamp,
    ]);

    return hash_hmac('sha256', $payload, $secret);
}

beforeEach(function () {
    config([
        'internal-services.allowed_service_ids' => ['test-service'],
        'internal-services.secret' => 'test-secret',
        'internal-services.timestamp_tolerance' => 300,
    ]);
});

test('request with valid hmac headers passes', function () {
    $serviceId = 'test-service';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = '/internal/v1/products/validate';
    $body = json_encode(['product_ids' => []]);

    $signature = computeHmacSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->withHeaders([
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ])->postJson($path, ['product_ids' => []]);

    $response->assertStatus(200);
});

test('request with invalid signature fails with 401', function () {
    $serviceId = 'test-service';
    $timestamp = (string) time();

    $response = $this->withHeaders([
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => 'invalid-signature',
    ])->postJson('/internal/v1/products/validate', ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid signature.',
        ]);
});

test('request with invalid service id fails with 401', function () {
    $serviceId = 'invalid-service';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = '/internal/v1/products/validate';
    $body = json_encode(['product_ids' => []]);

    $signature = computeHmacSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->withHeaders([
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ])->postJson($path, ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid service identifier.',
        ]);
});

test('request missing x-service-id header fails with 401', function () {
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = '/internal/v1/products/validate';
    $body = json_encode(['product_ids' => []]);

    $signature = computeHmacSignature('POST', $path, $body, 'test-service', $timestamp, $secret);

    $response = $this->withHeaders([
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ])->postJson($path, ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing required authentication headers.',
        ]);
});

test('request missing x-service-timestamp header fails with 401', function () {
    $serviceId = 'test-service';
    $secret = 'test-secret';
    $path = '/internal/v1/products/validate';
    $body = json_encode(['product_ids' => []]);

    $signature = computeHmacSignature('POST', $path, $body, $serviceId, (string) time(), $secret);

    $response = $this->withHeaders([
        'x-service-id' => $serviceId,
        'x-service-signature' => $signature,
    ])->postJson($path, ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing required authentication headers.',
        ]);
});

test('request missing x-service-signature header fails with 401', function () {
    $serviceId = 'test-service';
    $timestamp = (string) time();

    $response = $this->withHeaders([
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
    ])->postJson('/internal/v1/products/validate', ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing required authentication headers.',
        ]);
});

test('request with expired timestamp fails with 401', function () {
    $serviceId = 'test-service';
    $timestamp = (string) (time() - 600);
    $secret = 'test-secret';
    $path = '/internal/v1/products/validate';
    $body = json_encode(['product_ids' => []]);

    $signature = computeHmacSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->withHeaders([
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
    ])->postJson($path, ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Request timestamp is outside acceptable window.',
        ]);
});

test('request with all headers missing fails with 401', function () {
    $response = $this->postJson('/internal/v1/products/validate', ['product_ids' => []]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing required authentication headers.',
        ]);
});

