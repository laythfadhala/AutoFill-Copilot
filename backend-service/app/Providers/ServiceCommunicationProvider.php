<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ServiceRegistry;
use App\Services\ServiceCommunicator;
use App\Services\CircuitBreaker;

class ServiceCommunicationProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ServiceRegistry::class, function ($app) {
            return new ServiceRegistry();
        });

        $this->app->singleton(ServiceCommunicator::class, function ($app) {
            return new ServiceCommunicator($app->make(ServiceRegistry::class));
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
