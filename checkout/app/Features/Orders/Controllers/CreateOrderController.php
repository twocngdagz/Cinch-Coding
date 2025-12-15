<?php

declare(strict_types=1);

namespace App\Features\Orders\Controllers;

use App\Features\Orders\Actions\ValidateOrderAction;
use App\Features\Orders\Requests\ValidateOrderRequest;
use App\Models\Order;
use App\Services\InternalHttpClient;
use App\Support\RequestContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class CreateOrderController
{
    public function __construct(
        private readonly InternalHttpClient $emailClient
    ) {}
    public function __invoke(
        ValidateOrderRequest $request,
        ValidateOrderAction $action
    ): JsonResponse {
        $requestId = RequestContext::getRequestId();

        Log::channel('internal')->info('', [
            'event' => 'order_request_received',
            'request_id' => $requestId,
            'service' => 'checkout',
            'endpoint' => '/api/orders',
            'extra' => [
                'email' => $request->validated('email'),
                'items_count' => count($request->validated('items')),
            ],
        ]);

        $validated = $action->execute($request);

        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'] ?? [];

        $totalAmount = 0.0;
        foreach ($items as $item) {
            $totalAmount += (float) ($item['total_price'] ?? 0);
        }
        $order = Order::create([
            'email' => $validated['email'],
            'items' => $items,
            'total_amount' => $totalAmount,
        ]);

        Log::channel('internal')->info('', [
            'event' => 'order_created',
            'request_id' => $requestId,
            'service' => 'checkout',
            'endpoint' => '/api/orders',
            'extra' => [
                'order_id' => $order->id,
                'total_amount' => $totalAmount,
            ],
        ]);

        $this->emailClient->post('/internal/orders/receive', [
            'email' => $order->email,
            'items' => $order->items,
            'total_amount' => $order->total_amount,
        ]);

        return response()->json($order, 201);
    }
}
