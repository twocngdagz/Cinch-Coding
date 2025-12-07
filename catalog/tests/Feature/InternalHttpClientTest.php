<?php

use App\Services\InternalHttpClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

function computeClientSignature(
    string $method,
    string $path,
    string $body,
    string $serviceId,
    string $timestamp,
    string $secret
): string {
    $payload = implode("\n", [
        strtoupper($method),
        '/'.ltrim($path, '/'),
        $body,
        $serviceId,
        $timestamp,
    ]);

    return hash_hmac('sha256', $payload, $secret);
}

test('get request sends required headers', function () {
    Http::fake();

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');

    $client->get('/api/resource');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-service-id', 'catalog-service')
            && $request->hasHeader('x-service-timestamp')
            && $request->hasHeader('x-service-signature')
            && $request->hasHeader('Accept', 'application/json');
    });
});

test('post request sends required headers', function () {
    Http::fake();

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');

    $client->post('/api/resource', ['name' => 'test']);

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-service-id', 'catalog-service')
            && $request->hasHeader('x-service-timestamp')
            && $request->hasHeader('x-service-signature')
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Content-Type', 'application/json');
    });
});

test('get request signature matches expected hmac sha256 format', function () {
    Http::fake(function (Request $request) {
        $timestamp = $request->header('x-service-timestamp')[0];
        $signature = $request->header('x-service-signature')[0];
        $path = '/api/products';
        $secret = 'test-secret';
        $serviceId = 'catalog-service';

        $expectedSignature = computeClientSignature('GET', $path, '', $serviceId, $timestamp, $secret);

        expect($signature)->toBe($expectedSignature);

        return Http::response(['data' => 'ok'], 200);
    });

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->get('/api/products');
});

test('post request signature includes body in computation', function () {
    $data = ['product_id' => 123, 'quantity' => 5];
    $body = json_encode($data, JSON_THROW_ON_ERROR);

    Http::fake(function (Request $request) use ($body) {
        $timestamp = $request->header('x-service-timestamp')[0];
        $signature = $request->header('x-service-signature')[0];
        $path = '/api/orders';
        $secret = 'my-secret';
        $serviceId = 'order-service';

        $expectedSignature = computeClientSignature('POST', $path, $body, $serviceId, $timestamp, $secret);

        expect($signature)->toBe($expectedSignature);

        return Http::response(['success' => true], 201);
    });

    $client = new InternalHttpClient('https://example.com', 'my-secret', 'order-service');
    $client->post('/api/orders', $data);
});

test('request id header is forwarded when present', function () {
    Http::fake();

    request()->attributes->set('request_id', 'abc-123-def');

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->get('/api/resource');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-request-id', 'abc-123-def');
    });

    request()->attributes->remove('request_id');
});

test('request id header is not sent when not present', function () {
    Http::fake();

    request()->attributes->remove('request_id');

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->get('/api/resource');

    Http::assertSent(function (Request $request) {
        return ! $request->hasHeader('x-request-id');
    });
});

test('json response is decoded and returned', function () {
    Http::fake([
        '*' => Http::response(['id' => 42, 'name' => 'Product'], 200),
    ]);

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $result = $client->get('/api/products/42');

    expect($result)->toBe(['id' => 42, 'name' => 'Product']);
});

test('post request returns decoded json response', function () {
    Http::fake([
        '*' => Http::response(['created' => true, 'id' => 100], 201),
    ]);

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $result = $client->post('/api/products', ['name' => 'New Product']);

    expect($result)->toBe(['created' => true, 'id' => 100]);
});

test('throws exception when receiving non-2xx response', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Not Found'], 404),
    ]);

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->get('/api/products/999');
})->throws(RequestException::class);

test('throws exception for server error response', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->post('/api/products', ['name' => 'Test']);
})->throws(RequestException::class);

test('get request builds url correctly with query parameters', function () {
    Http::fake();

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->get('/api/products', ['page' => 1, 'limit' => 10]);

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'page=1')
            && str_contains($request->url(), 'limit=10');
    });
});

test('post request with empty data sends no body', function () {
    Http::fake(function (Request $request) {
        $timestamp = $request->header('x-service-timestamp')[0];
        $signature = $request->header('x-service-signature')[0];
        $path = '/api/ping';
        $secret = 'test-secret';
        $serviceId = 'catalog-service';

        $expectedSignature = computeClientSignature('POST', $path, '', $serviceId, $timestamp, $secret);

        expect($signature)->toBe($expectedSignature);

        return Http::response(['pong' => true], 200);
    });

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->post('/api/ping');
});

test('base url trailing slash is handled correctly', function () {
    Http::fake();

    $client = new InternalHttpClient('https://example.com/', 'test-secret', 'catalog-service');
    $client->get('/api/products');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://example.com/api/products';
    });
});

test('path leading slash is handled correctly', function () {
    Http::fake();

    $client = new InternalHttpClient('https://example.com', 'test-secret', 'catalog-service');
    $client->get('api/products');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://example.com/api/products';
    });
});
