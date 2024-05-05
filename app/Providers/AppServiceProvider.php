<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Observers\PointObserver;
use App\Models\ActionPoint;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ActionPoint::observe(PointObserver::class);
    }
}
