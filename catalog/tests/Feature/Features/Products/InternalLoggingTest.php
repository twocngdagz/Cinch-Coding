<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

pest()->use(RefreshDatabase::class);

function generateHmacSignatureForLoggingTest(
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

function setupFakeInternalChannel(object $fakeLogger): void
{
    $logManager = Log::getFacadeRoot();
    $logManager->extend('fake_internal', function () use ($fakeLogger) {
        return $fakeLogger;
    });
    Config::set('logging.channels.internal', [
        'driver' => 'fake_internal',
    ]);
}

beforeEach(function (): void {
    Config::set('internal-services.allowed_service_ids', ['checkout']);
    Config::set('internal-services.secret', 'test-secret');
    Config::set('internal-services.timestamp_tolerance', 300);
});

test('logs internal_validation_request on POST internal products validate endpoint', function (): void {
    $fakeLogger = createFakeInternalLogger();
    setupFakeInternalChannel($fakeLogger);

    $product = Product::factory()->create();
    Variant::factory()->for($product)->create();

    $payload = [
        'product_ids' => [$product->id],
        'variant_ids' => [],
    ];
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/v1/products/validate';
    $requestId = 'test-request-id-12345';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/v1/products/validate', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(200);

    $validationRequestLogs = $fakeLogger->getLogsByEvent('internal_validation_request');
    expect($validationRequestLogs)->toHaveCount(1);

    $logEntry = $validationRequestLogs[0];
    expect($logEntry['context']['event'])->toBe('internal_validation_request')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('catalog')
        ->and($logEntry['context']['endpoint'])->toBe($path)
        ->and($logEntry['context']['extra']['items_count'])->toBe(1);
});

test('logs internal_validation_success after successful validation', function (): void {
    $fakeLogger = createFakeInternalLogger();
    setupFakeInternalChannel($fakeLogger);

    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    Variant::factory()->for($product1)->create();
    Variant::factory()->for($product2)->create();

    $payload = [
        'product_ids' => [$product1->id, $product2->id],
        'variant_ids' => [],
    ];
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/v1/products/validate';
    $requestId = 'validation-success-request-id';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/v1/products/validate', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(200);

    $validationSuccessLogs = $fakeLogger->getLogsByEvent('internal_validation_success');
    expect($validationSuccessLogs)->toHaveCount(1);

    $logEntry = $validationSuccessLogs[0];
    expect($logEntry['context']['event'])->toBe('internal_validation_success')
        ->and($logEntry['context']['request_id'])->toBe($requestId)
        ->and($logEntry['context']['service'])->toBe('catalog')
        ->and($logEntry['context']['endpoint'])->toBe($path)
        ->and($logEntry['context']['extra']['validated_items'])->toBe(2);
});

test('logs product_validation_action_completed in domain action', function (): void {
    $fakeLogger = createFakeInternalLogger();
    setupFakeInternalChannel($fakeLogger);

    $product = Product::factory()->create();
    $variant = Variant::factory()->for($product)->create();

    $payload = [
        'product_ids' => [],
        'variant_ids' => [$variant->id],
    ];
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/v1/products/validate';
    $requestId = 'domain-action-request-id';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/v1/products/validate', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(200);

    $allLogs = $fakeLogger->logs;
    expect($allLogs)->toHaveCount(2);

    foreach ($allLogs as $log) {
        expect($log['context'])->toHaveKey('event')
            ->and($log['context'])->toHaveKey('request_id')
            ->and($log['context'])->toHaveKey('service')
            ->and($log['context']['request_id'])->toBe($requestId)
            ->and($log['context']['service'])->toBe('catalog');
    }
});

test('validation request log includes correct items_count for combined product and variant ids', function (): void {
    $fakeLogger = createFakeInternalLogger();
    setupFakeInternalChannel($fakeLogger);

    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    Variant::factory()->for($product1)->create();
    Variant::factory()->for($product2)->create();

    $payload = [
        'product_ids' => [$product1->id],
        'variant_ids' => [$product2->variants->first()->id],
    ];
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/v1/products/validate';
    $requestId = 'combined-items-request-id';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $response = $this->postJson('/internal/v1/products/validate', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $requestId,
    ]);

    $response->assertStatus(200);

    $validationRequestLog = $fakeLogger->getLogByEvent('internal_validation_request');
    expect($validationRequestLog)->not->toBeNull()
        ->and($validationRequestLog['context']['extra']['items_count'])->toBe(2);
});

test('request_id from header is propagated to all log entries', function (): void {
    $fakeLogger = createFakeInternalLogger();
    setupFakeInternalChannel($fakeLogger);

    $product = Product::factory()->create();
    Variant::factory()->for($product)->create();

    $payload = [
        'product_ids' => [$product->id],
        'variant_ids' => [],
    ];
    $body = json_encode($payload);
    $serviceId = 'checkout';
    $timestamp = (string) time();
    $secret = 'test-secret';
    $path = 'internal/v1/products/validate';
    $customRequestId = 'custom-propagated-request-id';

    $signature = generateHmacSignatureForLoggingTest('POST', $path, $body, $serviceId, $timestamp, $secret);

    $this->postJson('/internal/v1/products/validate', $payload, [
        'x-service-id' => $serviceId,
        'x-service-timestamp' => $timestamp,
        'x-service-signature' => $signature,
        'x-request-id' => $customRequestId,
    ]);

    foreach ($fakeLogger->logs as $log) {
        expect($log['context']['request_id'])->toBe($customRequestId);
    }
});
