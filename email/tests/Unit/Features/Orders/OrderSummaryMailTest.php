<?php

declare(strict_types=1);

use App\Features\Orders\Mail\OrderSummaryMail;

function createTestItems(): array
{
    return [
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
    ];
}

test('HTML version includes email', function (): void {
    $email = 'customer@example.com';
    $items = createTestItems();
    $totalAmount = 80.00;
    $requestId = 'request-123';

    $mailable = new OrderSummaryMail($email, $items, $totalAmount, $requestId);
    $html = $mailable->render();

    expect($html)->toContain($email);
});

test('HTML version includes total_amount', function (): void {
    $email = 'customer@example.com';
    $items = createTestItems();
    $totalAmount = 80.00;
    $requestId = 'request-123';

    $mailable = new OrderSummaryMail($email, $items, $totalAmount, $requestId);
    $html = $mailable->render();

    expect($html)->toContain((string) $totalAmount);
});

test('HTML version includes each item values', function (): void {
    $email = 'customer@example.com';
    $items = createTestItems();
    $totalAmount = 80.00;
    $requestId = 'request-123';

    $mailable = new OrderSummaryMail($email, $items, $totalAmount, $requestId);
    $html = $mailable->render();

    foreach ($items as $item) {
        expect($html)->toContain((string) $item['product_id']);
        expect($html)->toContain((string) $item['variant_id']);
        expect($html)->toContain((string) $item['quantity']);
        expect($html)->toContain((string) $item['unit_price']);
        expect($html)->toContain((string) $item['total_price']);
    }
});

test('text version includes email', function (): void {
    $email = 'customer@example.com';
    $items = createTestItems();
    $totalAmount = 80.00;
    $requestId = 'request-123';

    $mailable = new OrderSummaryMail($email, $items, $totalAmount, $requestId);
    $text = view('emails.order-summary-text', [
        'email' => $email,
        'items' => $items,
        'totalAmount' => $totalAmount,
    ])->render();

    expect($text)->toContain($email);
});

test('text version includes total_amount', function (): void {
    $email = 'customer@example.com';
    $items = createTestItems();
    $totalAmount = 80.00;
    $requestId = 'request-123';

    $text = view('emails.order-summary-text', [
        'email' => $email,
        'items' => $items,
        'totalAmount' => $totalAmount,
    ])->render();

    expect($text)->toContain((string) $totalAmount);
});

test('text version includes each item values', function (): void {
    $email = 'customer@example.com';
    $items = createTestItems();
    $totalAmount = 80.00;
    $requestId = 'request-123';

    $text = view('emails.order-summary-text', [
        'email' => $email,
        'items' => $items,
        'totalAmount' => $totalAmount,
    ])->render();

    foreach ($items as $item) {
        expect($text)->toContain((string) $item['product_id']);
        expect($text)->toContain((string) $item['variant_id']);
        expect($text)->toContain((string) $item['quantity']);
        expect($text)->toContain((string) $item['unit_price']);
        expect($text)->toContain((string) $item['total_price']);
    }
});
