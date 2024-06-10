<?php

namespace App\Providers;

use App\Services\CentralApiConnector;
use Illuminate\Support\ServiceProvider;

class CentralApiConnectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CentralApiConnector::class, function ($app) {
            return new CentralApiConnector(
                config('services.central-api.base_url'),
                config('services.central-api.api_key')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
