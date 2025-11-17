<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply in local/development environment
        if (app()->environment('local')) {
            // Prevent caching of HTML responses
            if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
            }
        }

        return $response;
    }
}

