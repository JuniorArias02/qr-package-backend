<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\GeocodingProviderInterface;
use App\Services\Geocoding\GeoapifyProvider;
use App\Contracts\RoutingProviderInterface;
use App\Services\Routing\GeoapifyRoutingProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeocodingProviderInterface::class, GeoapifyProvider::class);
        $this->app->bind(RoutingProviderInterface::class, GeoapifyRoutingProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
