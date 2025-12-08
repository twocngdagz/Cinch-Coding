<?php

declare(strict_types=1);

namespace App\Features\Orders\Controllers;

use App\Features\Orders\Actions\ValidateOrderAction;
use App\Features\Orders\Requests\ValidateOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

final class CreateOrderController
{
    public function __invoke(ValidateOrderRequest $request, ValidateOrderAction $action): JsonResponse
    {
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

        return response()->json($order, 201);
    }
}
