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
    public function __invoke(
        ValidateOrderRequest $request,
        ValidateOrderAction $action,
        InternalHttpClient $emailClient
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
        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }
        $order = Order::create([
            'email' => $validated['email'],
            'items' => $validated['items'],
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

        $emailClient->sendEmailNotification('/internal/orders/receive', $order->id);

        return response()->json($order, 201);
    }
}
