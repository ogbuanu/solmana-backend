<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Observers\PointObserver;
use App\Models\ActionPoint;
use App\Models\SocialAction;
use App\Models\TweetAction;
use App\Observers\TweetActionObserver;

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
        TweetAction::observe(TweetActionObserver::class);
        SocialAction::observe(TweetActionObserver::class);
    }
}
