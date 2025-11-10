<?php

namespace Modules\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!auth()->check() || !$user->hasAnyRole([
            'Super Admin',
            'Financial Admin',
            'Moderator',
            'Support Agent',
            // Legacy roles (backward compatibility)
            'Treasurer',
            'Secretary',
            'Auditor',
        ])) {
            abort(403, 'Unauthorized access to admin area.');
        }

        return $next($request);
    }
}

