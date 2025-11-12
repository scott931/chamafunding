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
             * @param string|null $version
             * @return string
             */
            function asset_versioned(string $path, ?string $version = null): string
            {
                $version = $version ?? config('app.version', '1.2');
                $assetUrl = asset($path);
                $separator = str_contains($assetUrl, '?') ? '&' : '?';
                return $assetUrl . $separator . 'v=' . $version;
            }
        }
    }
}
