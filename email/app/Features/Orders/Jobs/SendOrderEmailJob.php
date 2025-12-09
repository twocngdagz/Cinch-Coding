<?php

declare(strict_types=1);

namespace App\Features\Orders\Jobs;

use App\Features\Orders\Mail\OrderSummaryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendOrderEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $email;

    /** @var array<int, array<string, mixed>> */
    public array $items;

    public float $totalAmount;

    public string $requestId;

    /**
     * @param  array<string, mixed>  $orderPayload
     */
    public function __construct(array $orderPayload, string $requestId)
    {
        $this->email = $orderPayload['email'];
        $this->items = $orderPayload['items'];
        $this->totalAmount = (float) $orderPayload['total_amount'];
        $this->requestId = $requestId;
    }

    public function handle(): void
    {
        Log::channel('internal')->info('job_processing', [
            'request_id' => $this->requestId,
            'job' => 'SendOrderEmailJob',
            'recipient_email' => $this->email,
            'total_amount' => $this->totalAmount,
            'items_count' => count($this->items),
        ]);

        try {
            Mail::to($this->email)->send(
                new OrderSummaryMail(
                    email: $this->email,
                    items: $this->items,
                    totalAmount: $this->totalAmount,
                    requestId: $this->requestId,
                )
            );
            Log::channel('internal')->info('email_delivered', [
                'request_id' => $this->requestId,
                'job' => 'SendOrderEmailJob',
                'recipient_email' => $this->email,
                'status' => 'delivered',
            ]);
        } catch (\Throwable $e) {
            Log::channel('internal')->error('email_send_failed', [
                'request_id' => $this->requestId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
