<?php

declare(strict_types=1);

namespace App\Features\Orders\Jobs;

use App\Features\Orders\Mail\OrderSummaryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    /**
     * @param  array<string, mixed>  $orderPayload
     */
    public function __construct(array $orderPayload)
    {
        $this->email = $orderPayload['email'];
        $this->items = $orderPayload['items'];
        $this->totalAmount = (float) $orderPayload['total_amount'];
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(
            new OrderSummaryMail(
                email: $this->email,
                items: $this->items,
                totalAmount: $this->totalAmount,
            )
        );
    }
}
