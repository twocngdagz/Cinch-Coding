<?php

declare(strict_types=1);

use App\Features\Orders\Actions\ValidateOrderAction;
use App\Features\Orders\Requests\ValidateOrderRequest;
use App\Services\InternalHttpClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::post('/test-validate-order-action', fn (ValidateOrderRequest $request) => response()->json(['success' => true]));
});

it('calls internal http client with correct path and payload', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response([
            'items' => [
                [
                    'product_id' => 1,
                    'variant_id' => 2,
                    'quantity' => 3,
                    'price' => 1999,
                    'name' => 'Test Product',
                ],
            ],
        ]),
    ]);

    $client = new InternalHttpClient('http://catalog.test', 'test-secret', 'checkout');
    $action = new ValidateOrderAction($client);

    $request = ValidateOrderRequest::create('/test-validate-order-action', 'POST', [
        'email' => 'customer@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 2, 'quantity' => 3],
        ],
    ]);
    $request->setContainer(app());
    $request->validateResolved();

    $action->execute($request);

    Http::assertSent(function ($httpRequest) {
        return str_contains($httpRequest->url(), '/internal/products/validate')
            && $httpRequest->data() === [
                'items' => [
                    ['product_id' => 1, 'variant_id' => 2, 'quantity' => 3],
                ],
            ];
    });
});

it('returns enriched cart data from internal http client response', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response([
            'items' => [
                [
                    'product_id' => 10,
                    'variant_id' => 20,
                    'quantity' => 2,
                    'price' => 2500,
                    'name' => 'Premium Widget',
                    'sku' => 'PW-001',
                ],
                [
                    'product_id' => 11,
                    'variant_id' => 21,
                    'quantity' => 1,
                    'price' => 1500,
                    'name' => 'Basic Widget',
                    'sku' => 'BW-001',
                ],
            ],
        ]),
    ]);

    $client = new InternalHttpClient('http://catalog.test', 'test-secret', 'checkout');
    $action = new ValidateOrderAction($client);

    $request = ValidateOrderRequest::create('/test-validate-order-action', 'POST', [
        'email' => 'buyer@example.com',
        'items' => [
            ['product_id' => 10, 'variant_id' => 20, 'quantity' => 2],
            ['product_id' => 11, 'variant_id' => 21, 'quantity' => 1],
        ],
    ]);
    $request->setContainer(app());
    $request->validateResolved();

    $result = $action->execute($request);

    expect($result)->toHaveKey('email', 'buyer@example.com');
    expect($result)->toHaveKey('items');
    expect($result['items'])->toHaveCount(2);
    expect($result['items'][0])->toMatchArray([
        'product_id' => 10,
        'variant_id' => 20,
        'quantity' => 2,
        'price' => 2500,
        'name' => 'Premium Widget',
        'sku' => 'PW-001',
    ]);
    expect($result['items'][1])->toMatchArray([
        'product_id' => 11,
        'variant_id' => 21,
        'quantity' => 1,
        'price' => 1500,
        'name' => 'Basic Widget',
        'sku' => 'BW-001',
    ]);
});

it('throws exception when internal http client fails', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response(['error' => 'Catalog service unavailable'], 500),
    ]);

    $client = new InternalHttpClient('http://catalog.test', 'test-secret', 'checkout');
    $action = new ValidateOrderAction($client);

    $request = ValidateOrderRequest::create('/test-validate-order-action', 'POST', [
        'email' => 'user@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 1, 'quantity' => 1],
        ],
    ]);
    $request->setContainer(app());
    $request->validateResolved();

    expect(fn () => $action->execute($request))->toThrow(RequestException::class);
});

it('sends items payload in correct structure to catalog service', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response(['items' => []]),
    ]);

    $client = new InternalHttpClient('http://catalog.test', 'test-secret', 'checkout');
    $action = new ValidateOrderAction($client);

    $request = ValidateOrderRequest::create('/test-validate-order-action', 'POST', [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 5, 'variant_id' => 10, 'quantity' => 2],
            ['product_id' => 6, 'variant_id' => 12, 'quantity' => 4],
            ['product_id' => 7, 'variant_id' => 14, 'quantity' => 1],
        ],
    ]);
    $request->setContainer(app());
    $request->validateResolved();

    $action->execute($request);

    Http::assertSent(function ($httpRequest) {
        $data = $httpRequest->data();

        return isset($data['items'])
            && count($data['items']) === 3
            && $data['items'][0] === ['product_id' => 5, 'variant_id' => 10, 'quantity' => 2]
            && $data['items'][1] === ['product_id' => 6, 'variant_id' => 12, 'quantity' => 4]
            && $data['items'][2] === ['product_id' => 7, 'variant_id' => 14, 'quantity' => 1];
    });
});

it('returns empty items array when catalog response has no items', function (): void {
    Http::fake([
        '*/internal/products/validate' => Http::response([]),
    ]);

    $client = new InternalHttpClient('http://catalog.test', 'test-secret', 'checkout');
    $action = new ValidateOrderAction($client);

    $request = ValidateOrderRequest::create('/test-validate-order-action', 'POST', [
        'email' => 'empty@example.com',
        'items' => [
            ['product_id' => 999, 'variant_id' => 999, 'quantity' => 1],
        ],
    ]);
    $request->setContainer(app());
    $request->validateResolved();

    $result = $action->execute($request);

    expect($result)->toHaveKey('email', 'empty@example.com');
    expect($result)->toHaveKey('items');
    expect($result['items'])->toBe([]);
});
