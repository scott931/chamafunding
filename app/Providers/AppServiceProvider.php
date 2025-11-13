<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
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
        if ($this->app->environment('production') && str_starts_with((string) Config::get('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register helper function for versioned assets
        if (!function_exists('asset_versioned')) {
            /**
             * Generate a versioned asset URL with cache busting
             *
             * @param string $path
             * @return string
             */
            function asset_versioned($path)
            {
                $version = config('app.asset_version', time());
                return asset($path) . '?v=' . $version;
            }
        }

        // Note: Cache prevention middleware is registered in bootstrap/app.php
    }
}
