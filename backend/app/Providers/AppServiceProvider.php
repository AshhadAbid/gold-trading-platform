<?php

namespace App\Providers;

use App\Application\Pricing\MarketPriceService;
use App\Infrastructure\Pricing\GoldPriceOrgProvider;
use App\Infrastructure\Pricing\PakGoldProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MarketPriceService::class, fn () => new MarketPriceService([
            app(PakGoldProvider::class),
            app(GoldPriceOrgProvider::class),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
