<?php

namespace App\Providers;

use App\Models\Block;
use App\Models\Site;
use App\Observers\BlockObserver;
use App\Observers\SiteObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Block::observe(BlockObserver::class);
        Site::observe(SiteObserver::class);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
