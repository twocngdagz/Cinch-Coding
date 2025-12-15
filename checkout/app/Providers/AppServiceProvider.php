<?php

namespace App\Providers;

use App\Features\Orders\Actions\ValidateOrderAction;
use App\Features\Orders\Controllers\CreateOrderController;
use App\Services\InternalHttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(ValidateOrderAction::class)
            ->needs(InternalHttpClient::class)
            ->give(function () {
                return new InternalHttpClient(
                    baseUrl: (string) config('internal-services.services.catalog.base_url'),
                    secret: (string) config('internal-services.secret'),
                    serviceId: (string) config('internal-services.service_id')
                );
            });

        $this->app->when(CreateOrderController::class)
            ->needs(InternalHttpClient::class)
            ->give(function () {
                return new InternalHttpClient(
                    baseUrl: (string) config('internal-services.services.email.base_url'),
                    secret: (string) config('internal-services.secret'),
                    serviceId: (string) config('internal-services.service_id')
                );
            });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
