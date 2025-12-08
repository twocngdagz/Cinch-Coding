<?php

declare(strict_types=1);

namespace App\Features\Orders\Controllers;

use App\Features\Orders\Jobs\SendOrderEmailJob;
use App\Features\Orders\Requests\ReceiveOrderRequest;
use App\Support\RequestContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class ReceiveOrderController
{
    public function __invoke(ReceiveOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $requestId = RequestContext::getRequestId();

        Log::channel('internal')->info('order_received', [
            'event' => 'order_received',
            'request_id' => $requestId,
            'items_count' => count($validated['items']),
            'email' => $validated['email'],
        ]);

        dispatch(new SendOrderEmailJob($validated, $requestId));

        return response()->json(['status' => 'accepted'], 202);
    }
}
