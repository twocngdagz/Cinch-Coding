<?php

declare(strict_types=1);

namespace App\Features\Products\Controllers;

use App\Features\Products\Resources\ProductResource;
use App\Models\Product;
use App\Models\Variant;
use App\Support\RequestContext;
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
}
