<?php

declare(strict_types=1);

namespace App\Features\Products\Controllers;

use App\Features\Products\Resources\ProductResource;
use App\Models\Product;
use App\Models\Variant;
use App\Support\RequestContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

final class InternalProductController
{
    public function validateProducts(Request $request): AnonymousResourceCollection
    {
        $productIds = $request->input('product_ids', []);
        $variantIds = $request->input('variant_ids', []);
        $requestId = RequestContext::getRequestId();

        Log::channel('internal')->info('', [
            'event' => 'internal_validation_request',
            'request_id' => $requestId,
            'service' => 'catalog',
            'endpoint' => $request->path(),
            'extra' => [
                'items_count' => count($productIds) + count($variantIds),
            ],
        ]);

        $productsFromProductIds = [];
        $productsFromVariantIds = [];

        if (! empty($productIds)) {
            $productsFromProductIds = Product::with('variants')
                ->whereIn('id', $productIds)
                ->get()
                ->all();
        }

        if (! empty($variantIds)) {
            $productIdsFromVariants = Variant::whereIn('id', $variantIds)
                ->pluck('product_id')
                ->unique()
                ->toArray();

            $productsFromVariantIds = Product::with('variants')
                ->whereIn('id', $productIdsFromVariants)
                ->get()
                ->all();
        }

        $products = collect([...$productsFromProductIds, ...$productsFromVariantIds])
            ->unique('id')
            ->values();

        Log::channel('internal')->info('', [
            'event' => 'internal_validation_success',
            'request_id' => $requestId,
            'service' => 'catalog',
            'endpoint' => $request->path(),
            'extra' => [
                'validated_items' => $products->count(),
            ],
        ]);

        return ProductResource::collection($products);
    }

    public function getProductData(int $id): ProductResource
    {
        return new ProductResource(Product::with('variants')->findOrFail($id));
    }

    public function validateItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        /** @var array<int, array{product_id:int, variant_id:int, quantity:int}> $items */
        $items = $validated['items'];

        $variantIds = collect($items)->pluck('variant_id')->unique()->values()->all();

        $variants = Variant::query()
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $errors = [];
        $responseItems = [];
        $totalAmount = 0.0;

        foreach ($items as $index => $item) {
            $variant = $variants->get($item['variant_id']);

            if ($variant === null) {
                $errors["items.$index.variant_id"][] = 'Variant not found.';

                continue;
            }

            if ((int) $variant->product_id !== (int) $item['product_id']) {
                $errors["items.$index.product_id"][] = 'Variant does not belong to product.';

                continue;
            }

            if ((int) $variant->stock < (int) $item['quantity']) {
                $errors["items.$index.quantity"][] = 'Insufficient stock.';

                continue;
            }

            $unitPrice = (float) $variant->price;
            $totalPrice = round($unitPrice * (int) $item['quantity'], 2);

            $responseItems[] = [
                'product_id' => (int) $item['product_id'],
                'variant_id' => (int) $item['variant_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];

            $totalAmount += $totalPrice;
        }

        if ($errors !== []) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'items' => $responseItems,
            'total_amount' => round($totalAmount, 2),
        ]);
    }
}
