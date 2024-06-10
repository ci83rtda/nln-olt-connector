<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OltConnector;

class OltConnectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OltConnector::class, function ($app) {
            return new OltConnector(
                config('services.olt.host'),
                config('services.olt.username'),
                config('services.olt.password'),
                config('services.olt.enable_password')
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
