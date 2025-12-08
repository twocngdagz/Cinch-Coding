<?php

declare(strict_types=1);

namespace App\Features\Orders\Controllers;

use App\Features\Orders\Jobs\SendOrderEmailJob;
use App\Features\Orders\Requests\ReceiveOrderRequest;
use Illuminate\Http\JsonResponse;

final class ReceiveOrderController
{
    public function __invoke(ReceiveOrderRequest $request): JsonResponse
    {
        dispatch(new SendOrderEmailJob($request->validated()));

        return response()->json(['status' => 'accepted'], 202);
    }
}
