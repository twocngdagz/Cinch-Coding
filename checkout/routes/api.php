<?php

declare(strict_types=1);
use App\Features\Orders\Controllers\CreateOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', CreateOrderController::class);
