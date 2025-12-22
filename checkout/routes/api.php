<?php

declare(strict_types=1);

use App\Features\Cart\Controllers\CartController;
use App\Features\Orders\Controllers\CreateOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/orders', CreateOrderController::class);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{variantId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{variantId}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);
});
