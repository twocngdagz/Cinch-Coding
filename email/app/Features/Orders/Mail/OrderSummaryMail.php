<?php

declare(strict_types=1);

namespace App\Features\Orders\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrderSummaryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public array $items,
        public float $totalAmount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-summary',
            text: 'emails.order-summary-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
