<?php

namespace App\Http\Middleware;

use App\Events\UserActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Routes that should be excluded from activity tracking
     */
    protected $excludedRoutes = [
        'api/v1/health',
        'up',
    ];

    /**
     * Activity types that should be tracked
     */
    protected $trackableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track if user is authenticated and method is trackable
        if (auth()->check() && in_array($request->method(), $this->trackableMethods)) {
            // Skip excluded routes
            if (!$this->shouldTrack($request)) {
                return $response;
            }

            // Determine activity type based on route
            $activityType = $this->getActivityType($request);
            
            // Only log significant activities (not every page view)
            if ($activityType) {
                $description = $this->getActivityDescription($request, $activityType);
                
                event(new UserActivity(
                    auth()->user(),
                    $activityType,
                    $description,
                    $request,
                    $this->getMetadata($request)
                ));
            }
        }

        return $response;
    }

    /**
     * Check if the request should be tracked
     */
    protected function shouldTrack(Request $request): bool
    {
        $path = $request->path();
        
        foreach ($this->excludedRoutes as $excluded) {
            if (str_contains($path, $excluded)) {
                return false;
            }
        }

        // Don't track static assets
        if (preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$/i', $path)) {
            return false;
        }

        return true;
    }

    /**
     * Get activity type based on request
     */
    protected function getActivityType(Request $request): ?string
    {
        $path = $request->path();
        $method = $request->method();

        // Only track significant actions, not every GET request
        if ($method === 'GET') {
            // Track important page views
            if (str_contains($path, 'admin')) {
                return 'admin_page_view';
            }
            if (str_contains($path, 'dashboard')) {
                return 'dashboard_view';
            }
            if (str_contains($path, 'campaign')) {
                return 'campaign_view';
            }
            // Skip other GET requests to avoid too much logging
            return null;
        }

        // Track all POST, PUT, PATCH, DELETE actions
        return match($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'action',
        };
    }

    /**
     * Get activity description
     */
    protected function getActivityDescription(Request $request, string $activityType): string
    {
        $path = $request->path();
        $method = $request->method();

        $resource = $this->extractResource($path);

        return match($activityType) {
            'admin_page_view' => "Viewed admin page: {$resource}",
            'dashboard_view' => "Viewed dashboard",
            'campaign_view' => "Viewed campaign",
            'create' => "Created {$resource}",
            'update' => "Updated {$resource}",
            'delete' => "Deleted {$resource}",
            default => "Performed action: {$method} {$path}",
        };
    }

    /**
     * Extract resource name from path
     */
    protected function extractResource(string $path): string
    {
        $parts = explode('/', $path);
        $parts = array_filter($parts, fn($part) => !empty($part) && !is_numeric($part));
        
        return end($parts) ?: 'resource';
    }

    /**
     * Get additional metadata for the activity
     */
    protected function getMetadata(Request $request): array
    {
        return [
            'route' => $request->route()?->getName(),
            'controller' => $request->route()?->getActionName(),
            'referer' => $request->header('referer'),
        ];
    }
}
