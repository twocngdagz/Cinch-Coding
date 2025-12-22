<?php

declare(strict_types=1);

namespace App\Features\Cart\Controllers;

use App\Features\Cart\Requests\AddCartItemRequest;
use App\Features\Cart\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CartController
{
    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return $this->cartResponse($cart);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $validated = $request->validated();

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'variant_id' => $validated['variant_id'],
        ]);

        $item->quantity = $item->exists
            ? $item->quantity + $validated['quantity']
            : $validated['quantity'];
        $item->save();

        return $this->cartResponse($cart->refresh());
    }

    public function updateItem(UpdateCartItemRequest $request, string $variantId): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $quantity = $request->validated()['quantity'];

        if ($quantity === 0) {
            $cart->items()->where('variant_id', $variantId)->delete();

            return $this->cartResponse($cart->refresh());
        }

        CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'variant_id' => $variantId,
            ],
            ['quantity' => $quantity]
        );

        return $this->cartResponse($cart->refresh());
    }

    public function removeItem(Request $request, string $variantId): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart->items()->where('variant_id', $variantId)->delete();

        return $this->cartResponse($cart->refresh());
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart->items()->delete();

        return $this->cartResponse($cart->refresh());
    }

    private function resolveCart(Request $request): Cart
    {
        $token = $this->extractToken($request);

        if ($token !== null) {
            return Cart::firstOrCreate(['token' => $token]);
        }

        return Cart::create(['token' => (string) Str::uuid()]);
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->header('X-Cart-Token');
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $queryToken = $request->query('cart_token');
        if (is_string($queryToken) && $queryToken !== '') {
            return $queryToken;
        }

        return null;
    }

    private function cartResponse(Cart $cart): JsonResponse
    {
        $cart->load('items');

        return response()->json([
            'cart_token' => $cart->token,
            'items' => $cart->items
                ->map(fn (CartItem $item): array => [
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                ])
                ->values()
                ->all(),
        ])->header('X-Cart-Token', $cart->token);
    }
}
