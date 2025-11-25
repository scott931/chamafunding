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
        $contentType = $response->headers->get('Content-Type', '');
        $isHtml = str_contains($contentType, 'text/html');
        $isJson = str_contains($contentType, 'application/json');
        
        // Apply cache prevention to HTML pages and JSON API responses (especially auth endpoints)
        if ($isHtml || ($isJson && $request->is('api/*'))) {
            // Aggressive cache prevention headers
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->set('X-Accel-Expires', '0');
            
            // Additional headers to prevent proxy caching
            $response->headers->set('Vary', 'Accept-Encoding');
            
            // For HTML, add ETag and Last-Modified to prevent caching
            if ($isHtml) {
                $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                $response->headers->set('ETag', md5($response->getContent() . time()));
            }
        }

        return $response;
    }
}

