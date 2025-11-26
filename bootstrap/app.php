<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Env;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'admin.role' => \Modules\Admin\Http\Middleware\EnsureUserHasAdminRole::class,
        ]);

        // Prevent caching in all environments for authenticated routes and API
        // This ensures logout works properly and prevents back button access
        $middleware->web(append: [
            \App\Http\Middleware\PreventCache::class,
        ]);
        
        // Also apply to API routes
        $middleware->api(append: [
            \App\Http\Middleware\PreventCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions to help with debugging
        $exceptions->report(function (\Throwable $e) {
            $message = 'Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            
            // Log via Laravel (only if Log facade is available - may not be during bootstrap)
            try {
                if (class_exists(\Illuminate\Support\Facades\Log::class)) {
                    $app = app();
                    if ($app && method_exists($app, 'bound') && $app->bound('log')) {
                        \Log::error($message, [
                            'exception' => $e,
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            } catch (\Throwable $logException) {
                // Log facade not available yet, continue with error_log
            }
            
            // Also output directly to stderr to ensure we see it
            error_log($message);
            error_log('Stack trace: ' . $e->getTraceAsString());
        });
        
        // Render exceptions in a user-friendly way
        $exceptions->render(function (\Throwable $e, $request) {
            // For the root route, always redirect to login on error
            if ($request->is('/')) {
                error_log('Exception on root route: ' . $e->getMessage());
                try {
                    return redirect()->route('login');
                } catch (\Exception $redirectException) {
                    return response('Service temporarily unavailable. Please try again later.', 503);
                }
            }
        });
    })->create();
