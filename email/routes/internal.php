<?php

declare(strict_types=1);

use App\Features\Orders\Controllers\ReceiveOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('hmac')->group(function (): void {
    Route::post('/orders/receive', ReceiveOrderController::class);
});
