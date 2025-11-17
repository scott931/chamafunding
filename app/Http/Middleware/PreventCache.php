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

        // Apply aggressive cache prevention in both local and production
        // This ensures changes are reflected immediately without browser caching
        $isHtml = $response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html');
        
        if ($isHtml) {
            // Aggressive cache prevention headers
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->headers->set('ETag', md5($response->getContent() . time()));
            
            // Additional headers to prevent proxy caching
            $response->headers->set('X-Accel-Expires', '0');
            $response->headers->set('Vary', 'Accept-Encoding');
        }

        return $response;
    }
}

