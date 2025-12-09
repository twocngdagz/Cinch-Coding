<?php

declare(strict_types=1);

use App\Services\InternalHttpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->singleton(InternalHttpClient::class, function () {
        return new InternalHttpClient(
            'http://catalog.test',
            'test-secret',
            'checkout'
        );
    });
});

function createFakeInternalLogger(): object
{
    return new class implements LoggerInterface
    {
        public array $logs = [];

        public function emergency(\Stringable|string $message, array $context = []): void
        {
            $this->log('emergency', $message, $context);
        }

        public function alert(\Stringable|string $message, array $context = []): void
        {
            $this->log('alert', $message, $context);
        }

        public function critical(\Stringable|string $message, array $context = []): void
        {
            $this->log('critical', $message, $context);
        }

        public function error(\Stringable|string $message, array $context = []): void
        {
            $this->log('error', $message, $context);
        }

        public function warning(\Stringable|string $message, array $context = []): void
        {
            $this->log('warning', $message, $context);
        }

        public function notice(\Stringable|string $message, array $context = []): void
        {
            $this->log('notice', $message, $context);
        }

        public function info(\Stringable|string $message, array $context = []): void
        {
            $this->log('info', $message, $context);
        }

        public function debug(\Stringable|string $message, array $context = []): void
        {
            $this->log('debug', $message, $context);
        }

        public function log($level, \Stringable|string $message, array $context = []): void
        {
            $this->logs[] = [
                'level' => $level,
                'message' => (string) $message,
                'context' => $context,
            ];
        }

        public function getLogsByEvent(string $event): array
        {
            return array_values(array_filter($this->logs, fn (array $log): bool => ($log['context']['event'] ?? '') === $event
            ));
        }

        public function getLogByEvent(string $event): ?array
        {
            $logs = $this->getLogsByEvent($event);

            return $logs[0] ?? null;
        }
    };
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
            ],
            [
                'product_id' => 2,
                'variant_id' => 20,
                'quantity' => 1,
            ],
        ],
    ];
}

function fakeCatalogResponse(): array
{
    return [
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2, 'price' => 25.00],
            ['product_id' => 2, 'variant_id' => 20, 'quantity' => 1, 'price' => 30.00],
        ],
    ];
}

test('logs order_request_received on POST /api/orders', function (): void {
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    Http::fake([
        '*/internal/products/validate' => Http::response(fakeCatalogResponse(), 200),
        '*/internal/orders/receive' => Http::response([], 202),
    ]);

    $payload = validOrderPayload();
    $requestId = 'test-request-id-12345';

    $response = $this->postJson('/api/orders', $payload, [
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(201);

    $orderReceivedLogs = $fakeLogger->getLogsByEvent('order_request_received');
    expect($orderReceivedLogs)->toHaveCount(1);

    $logEntry = $orderReceivedLogs[0];
    expect($logEntry['context']['event'])->toBe('order_request_received')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('checkout')
        ->and($logEntry['context']['endpoint'])->toBe('/api/orders')
        ->and($logEntry['context']['extra']['items_count'])->toBe(2);
});

test('logs catalog_validation_call before internal catalog request', function (): void {
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    Http::fake([
        '*/internal/products/validate' => Http::response(fakeCatalogResponse(), 200),
        '*/internal/orders/receive' => Http::response([], 202),
    ]);

    $payload = validOrderPayload();
    $requestId = 'catalog-validation-request-id';

    $response = $this->postJson('/api/orders', $payload, [
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(201);

    $catalogLogs = $fakeLogger->getLogsByEvent('catalog_validation_call');
    expect($catalogLogs)->toHaveCount(1);

    $logEntry = $catalogLogs[0];
    expect($logEntry['context']['event'])->toBe('catalog_validation_call')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('checkout')
        ->and($logEntry['context']['extra']['items_count'])->toBe(2);
});

test('logs order_created after order is successfully persisted', function (): void {
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    Http::fake([
        '*/internal/products/validate' => Http::response(fakeCatalogResponse(), 200),
        '*/internal/orders/receive' => Http::response([], 202),
    ]);

    $payload = validOrderPayload();
    $requestId = 'order-created-request-id';

    $response = $this->postJson('/api/orders', $payload, [
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(201);

    $orderCreatedLogs = $fakeLogger->getLogsByEvent('order_created');
    expect($orderCreatedLogs)->toHaveCount(1);

    $logEntry = $orderCreatedLogs[0];
    expect($logEntry['context']['event'])->toBe('order_created')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('checkout')
        ->and($logEntry['context']['extra']['order_id'])->toBeInt()
        ->and($logEntry['context']['extra']['total_amount'])->toEqual(80);
});

test('logs email_notification_request before calling Email service', function (): void {
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    Http::fake([
        '*/internal/products/validate' => Http::response([
            'items' => [
                ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2, 'price' => 25.00],
            ],
        ], 200),
        '*/internal/orders/receive' => Http::response([], 202),
    ]);

    $payload = [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2],
        ],
    ];
    $requestId = 'email-notification-request-id';

    $response = $this->postJson('/api/orders', $payload, [
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(201);

    $emailRequestLogs = $fakeLogger->getLogsByEvent('email_notification_request');
    expect($emailRequestLogs)->toHaveCount(1);

    $logEntry = $emailRequestLogs[0];
    expect($logEntry['context']['event'])->toBe('email_notification_request')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('checkout')
        ->and($logEntry['context']['extra']['order_id'])->toBeInt();
});

test('logs email_notification_acknowledged when Email responds 202', function (): void {
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    Http::fake([
        '*/internal/products/validate' => Http::response([
            'items' => [
                ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2, 'price' => 25.00],
            ],
        ], 200),
        '*/internal/orders/receive' => Http::response([], 202),
    ]);

    $payload = [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2],
        ],
    ];
    $requestId = 'email-acknowledged-request-id';

    $response = $this->postJson('/api/orders', $payload, [
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(201);

    $emailAcknowledgedLogs = $fakeLogger->getLogsByEvent('email_notification_acknowledged');
    expect($emailAcknowledgedLogs)->toHaveCount(1);

    $logEntry = $emailAcknowledgedLogs[0];
    expect($logEntry['context']['event'])->toBe('email_notification_acknowledged')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('checkout')
        ->and($logEntry['context']['extra']['order_id'])->toBeInt();
});
