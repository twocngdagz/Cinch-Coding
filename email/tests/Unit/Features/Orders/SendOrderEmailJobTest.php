<?php

declare(strict_types=1);

use App\Features\Orders\Jobs\SendOrderEmailJob;
use App\Features\Orders\Mail\OrderSummaryMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

function createJobPayload(): array
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

test('job sends OrderSummaryMail', function (): void {
    $payload = createJobPayload();
    $requestId = 'test-request-id';

    $job = new SendOrderEmailJob($payload, $requestId);
    $job->handle();

    Mail::assertSent(OrderSummaryMail::class);
});

test('mail is sent to correct email address', function (): void {
    $payload = createJobPayload();
    $requestId = 'test-request-id';

    $job = new SendOrderEmailJob($payload, $requestId);
    $job->handle();

    Mail::assertSent(OrderSummaryMail::class, function (OrderSummaryMail $mail) use ($payload): bool {
        return $mail->hasTo($payload['email']);
    });
});

test('mailable fields match input', function (): void {
    $payload = createJobPayload();
    $requestId = 'test-request-id';

    $job = new SendOrderEmailJob($payload, $requestId);
    $job->handle();

    Mail::assertSent(OrderSummaryMail::class, function (OrderSummaryMail $mail) use ($payload, $requestId): bool {
        return $mail->email === $payload['email']
            && $mail->items === $payload['items']
            && $mail->totalAmount === (float) $payload['total_amount']
            && $mail->requestId === $requestId;
    });
});

test('job properties are correctly set from payload', function (): void {
    $payload = createJobPayload();
    $requestId = 'test-request-id';

    $job = new SendOrderEmailJob($payload, $requestId);

    expect($job->email)->toBe($payload['email']);
    expect($job->items)->toBe($payload['items']);
    expect($job->totalAmount)->toBe((float) $payload['total_amount']);
    expect($job->requestId)->toBe($requestId);
});
