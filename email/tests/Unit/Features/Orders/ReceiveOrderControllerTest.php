<?php

declare(strict_types=1);

use App\Features\Orders\Controllers\ReceiveOrderController;
use App\Features\Orders\Jobs\SendOrderEmailJob;
use App\Features\Orders\Requests\ReceiveOrderRequest;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

test('controller dispatches SendOrderEmailJob with expected payload', function (): void {
    $payload = [
        'email' => 'test@example.com',
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

    $request = ReceiveOrderRequest::create('/internal/orders/receive', 'POST', $payload);
    $request->setContainer(app());
    $request->setRouteResolver(fn () => null);
    $request->validateResolved();

    $requestId = 'test-request-id-123';
    $request->attributes->set('request_id', $requestId);

    app()->instance('request', $request);

    $controller = new ReceiveOrderController;
    $response = $controller($request);

    expect($response->status())->toBe(202);
    expect($response->getData(true))->toBe(['status' => 'accepted']);

    Queue::assertPushed(SendOrderEmailJob::class, function (SendOrderEmailJob $job) use ($payload): bool {
        return $job->email === $payload['email']
            && $job->items === $payload['items']
            && $job->totalAmount === (float) $payload['total_amount'];
    });
});

test('request_id is passed to the job', function (): void {
    $payload = [
        'email' => 'test@example.com',
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

    $request = ReceiveOrderRequest::create('/internal/orders/receive', 'POST', $payload);
    $request->setContainer(app());
    $request->setRouteResolver(fn () => null);
    $request->validateResolved();

    $requestId = 'unique-request-id-456';
    $request->attributes->set('request_id', $requestId);

    app()->instance('request', $request);

    $controller = new ReceiveOrderController;
    $controller($request);

    Queue::assertPushed(SendOrderEmailJob::class);

    $pushedJobs = Queue::pushedJobs()[SendOrderEmailJob::class] ?? [];
    expect($pushedJobs)->toHaveCount(1);

    $job = $pushedJobs[0]['job'];
    expect($job->requestId)->toBe($requestId);
});
