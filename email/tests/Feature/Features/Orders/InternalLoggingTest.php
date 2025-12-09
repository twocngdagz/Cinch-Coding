<?php

declare(strict_types=1);

use App\Features\Orders\Jobs\SendOrderEmailJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Psr\Log\LoggerInterface;

function generateHmacSignatureForLoggingTest(string $method, string $path, string $body, string $serviceId, string $timestamp, string $secret): string
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

function validOrderPayloadForLoggingTest(): array
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
            [
                'product_id' => 2,
                'variant_id' => 20,
                'quantity' => 1,
                'unit_price' => 30.00,
                'total_price' => 30.00,
            ],
        ],
        'total_amount' => 80.00,
    ];
}

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
            return array_values(array_filter($this->logs, fn (array $log): bool => ($log['context']['event'] ?? $log['message']) === $event
            ));
        }

        public function getLogByMessage(string $message): ?array
        {
            foreach ($this->logs as $log) {
                if ($log['message'] === $message) {
                    return $log;
                }
            }

            return null;
        }
    };
}

beforeEach(function (): void {
    Config::set('internal-services.allowed_service_ids', ['checkout']);
    Config::set('internal-services.secret', 'test-secret');
    Config::set('internal-services.timestamp_tolerance', 300);
});

test('internal endpoint logs order_received event', function (): void {
    Queue::fake();
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    $payload = validOrderPayloadForLoggingTest();
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/orders/receive';
    $requestId = 'test-request-id-12345';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/orders/receive', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(202);

    expect($fakeLogger->logs)->toHaveCount(1);

    $orderReceivedLogs = $fakeLogger->getLogsByEvent('order_received');
    expect($orderReceivedLogs)->toHaveCount(1);

    $logEntry = $orderReceivedLogs[0];
    expect($logEntry['message'])->toBe('order_received')
        ->and($logEntry['context']['event'])->toBe('order_received')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['items_count'])->toBe(2)
        ->and($logEntry['context']['email'])->toBe('customer@example.com');
});

test('job execution logs job_processing and email_delivered', function (): void {
    Mail::fake();
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    $orderPayload = [
        'email' => 'test@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 2, 'unit_price' => 25.00, 'total_price' => 50.00],
        ],
        'total_amount' => 50.00,
    ];
    $requestId = 'job-request-id-67890';

    $job = new SendOrderEmailJob($orderPayload, $requestId);
    $job->handle();

    expect($fakeLogger->logs)->toHaveCount(2);

    $processingLog = $fakeLogger->getLogByMessage('job_processing');
    expect($processingLog)->not->toBeNull()
        ->and($processingLog['context']['request_id'])->toBe($requestId)
        ->and($processingLog['context']['job'])->toBe('SendOrderEmailJob')
        ->and($processingLog['context']['recipient_email'])->toBe('test@example.com')
        ->and($processingLog['context']['total_amount'])->toBe(50.00)
        ->and($processingLog['context']['items_count'])->toBe(1);

    $deliveredLog = $fakeLogger->getLogByMessage('email_delivered');
    expect($deliveredLog)->not->toBeNull()
        ->and($deliveredLog['context']['request_id'])->toBe($requestId)
        ->and($deliveredLog['context']['job'])->toBe('SendOrderEmailJob')
        ->and($deliveredLog['context']['recipient_email'])->toBe('test@example.com')
        ->and($deliveredLog['context']['status'])->toBe('delivered');
});

test('job logs include correct items_count and total_amount', function (): void {
    Mail::fake();
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    $orderPayload = [
        'email' => 'user@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 1, 'unit_price' => 10.00, 'total_price' => 10.00],
            ['product_id' => 2, 'variant_id' => 20, 'quantity' => 2, 'unit_price' => 15.00, 'total_price' => 30.00],
            ['product_id' => 3, 'variant_id' => 30, 'quantity' => 3, 'unit_price' => 20.00, 'total_price' => 60.00],
        ],
        'total_amount' => 100.00,
    ];
    $requestId = 'items-count-test-request';

    $job = new SendOrderEmailJob($orderPayload, $requestId);
    $job->handle();

    $processingLog = $fakeLogger->getLogByMessage('job_processing');
    expect($processingLog)->not->toBeNull()
        ->and($processingLog['context']['items_count'])->toBe(3)
        ->and($processingLog['context']['total_amount'])->toBe(100.00);
});

test('controller log includes request_id from header', function (): void {
    Queue::fake();
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    $payload = validOrderPayloadForLoggingTest();
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/orders/receive';
    $customRequestId = 'custom-propagated-request-id';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $this->postJson('/internal/orders/receive', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $customRequestId,
    ]);

    $orderReceivedLog = $fakeLogger->getLogByMessage('order_received');
    expect($orderReceivedLog)->not->toBeNull()
        ->and($orderReceivedLog['context']['request_id'])->toBe($customRequestId);
});

test('job propagates request_id to all log entries', function (): void {
    Mail::fake();
    $fakeLogger = createFakeInternalLogger();
    Log::shouldReceive('channel')
        ->with('internal')
        ->andReturn($fakeLogger);

    $orderPayload = [
        'email' => 'propagation@example.com',
        'items' => [
            ['product_id' => 1, 'variant_id' => 10, 'quantity' => 1, 'unit_price' => 50.00, 'total_price' => 50.00],
        ],
        'total_amount' => 50.00,
    ];
    $requestId = 'propagation-test-request-id';

    $job = new SendOrderEmailJob($orderPayload, $requestId);
    $job->handle();

    foreach ($fakeLogger->logs as $log) {
        expect($log['context']['request_id'])->toBe($requestId);
    }
});
