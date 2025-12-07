<?php

declare(strict_types=1);

use App\Features\Products\Controllers\InternalProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/products/validate', [InternalProductController::class, 'validateProducts']);
    Route::get('/products/{id}', [InternalProductController::class, 'getProductData']);
});


