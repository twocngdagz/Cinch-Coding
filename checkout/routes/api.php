<?php

declare(strict_types=1);
use App\Features\Orders\Controllers\CreateOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/orders', CreateOrderController::class);
});
