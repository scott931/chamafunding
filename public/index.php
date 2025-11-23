<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Suppress specific deprecation warnings from vendor Laravel config
// This is a known issue in Laravel 12.0 that will be fixed in future versions
// We've already fixed it in our local config/database.php
if (PHP_VERSION_ID >= 80500) {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        // Suppress only the specific PDO::MYSQL_ATTR_SSL_CA deprecation warnings from vendor Laravel config
        if ($errno === E_DEPRECATED && 
            str_contains($errstr, 'PDO::MYSQL_ATTR_SSL_CA is deprecated') &&
            str_contains($errfile, 'vendor/laravel/framework/config/database.php')) {
            return true; // Suppress this specific warning
        }
        // Let other errors be handled normally
        return false;
    }, E_DEPRECATED);
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
