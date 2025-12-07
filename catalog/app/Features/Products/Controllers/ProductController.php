<?php

namespace App\Features\Products\Controllers;

use App\Features\Products\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::with('variants')->get());
    }

    public function show(int $id): ProductResource
    {
        return new ProductResource(Product::with('variants')->findOrFail($id));
    }
}
