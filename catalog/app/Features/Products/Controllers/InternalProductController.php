<?php

declare(strict_types=1);

namespace App\Features\Products\Controllers;

use App\Features\Products\Resources\ProductResource;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InternalProductController
{
    public function validateProducts(Request $request): AnonymousResourceCollection
    {
        $productIds = $request->input('product_ids', []);
        $variantIds = $request->input('variant_ids', []);

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

        return ProductResource::collection($products);
    }

    public function getProductData(int $id): ProductResource
    {
        return new ProductResource(Product::with('variants')->findOrFail($id));
    }
}
