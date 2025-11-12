<?php

if (!function_exists('asset_versioned')) {
    /**
     * Generate an asset URL with a version query string to bust caches.
     *
     * @param string $path
     * @return string
     */
    function asset_versioned(string $path): string
    {
        $version = config('app.asset_version', '1.0.0');

        return asset($path) . '?v=' . $version;
    }
}


