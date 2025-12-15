<?php

declare(strict_types=1);

namespace App\Features\Orders\Actions;

use App\Features\Orders\Requests\ValidateOrderRequest;
use App\Services\InternalHttpClient;
use Illuminate\Http\Client\RequestException;

final class ValidateOrderAction
{
    private InternalHttpClient $catalogClient;

    public function __construct(InternalHttpClient $catalogClient)
    {
        $this->catalogClient = $catalogClient;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function execute(ValidateOrderRequest $request): array
    {
        /** @var array<int, array{product_id: int, variant_id: int, quantity: int}> $items */
        $items = $request->validated('items');

        /** @var array<string, mixed> $response */
        $response = $this->catalogClient->post('/internal/v1/products/validate-items', [
            'items' => $items,
        ]);

        return [
            'email' => $request->validated('email'),
            'items' => $response['items'] ?? [],
            'total_amount' => $response['total_amount'] ?? 0,
        ];
    }
}
