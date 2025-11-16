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
        // Ensure APP_KEY is set - this is critical for encryption
        // This check happens early to catch missing APP_KEY before encryption services are used
        if (empty(config('app.key'))) {
            $appKey = env('APP_KEY');
            if (empty($appKey)) {
                \Log::error('APP_KEY is not set! This will cause encryption errors.');
                error_log('CRITICAL: APP_KEY environment variable is not set!');
                // Don't throw exception here as it would prevent the app from booting
                // The exception handler will catch encryption errors and handle them gracefully
            } else {
                // Force set the key if it's in env but not in config (shouldn't happen, but just in case)
                config(['app.key' => $appKey]);
            }
        }
        
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
