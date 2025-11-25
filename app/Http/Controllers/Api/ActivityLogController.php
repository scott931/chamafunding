<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get activity logs for the authenticated user (or all logs for admins)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Admins can see all logs, regular users only see their own
        $query = $user->isAdmin() 
            ? ActivityLog::query()
            : ActivityLog::forUser($user->id);
        
        $query->with('user:id,name,email')
            ->orderByDesc('created_at');

        // Filter by activity type
        if ($request->has('activity_type')) {
            $query->ofType($request->activity_type);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Paginate results
        $perPage = min($request->get('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get activity log statistics (all logs for admins, user's logs for regular users)
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Admins can see all logs, regular users only see their own
        $baseQuery = $user->isAdmin() 
            ? ActivityLog::query()
            : ActivityLog::forUser($user->id);

        $stats = [
            'total_activities' => (clone $baseQuery)->count(),
            'activities_by_type' => (clone $baseQuery)
                ->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->pluck('count', 'activity_type'),
            'devices_used' => (clone $baseQuery)
                ->selectRaw('device_type, COUNT(*) as count')
                ->whereNotNull('device_type')
                ->groupBy('device_type')
                ->pluck('count', 'device_type'),
            'browsers_used' => (clone $baseQuery)
                ->selectRaw('browser, COUNT(*) as count')
                ->whereNotNull('browser')
                ->groupBy('browser')
                ->pluck('count', 'browser'),
            'locations' => (clone $baseQuery)
                ->selectRaw('country, city, COUNT(*) as count')
                ->whereNotNull('country')
                ->groupBy('country', 'city')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn($log) => [
                    'country' => $log->country,
                    'city' => $log->city,
                    'count' => $log->count,
                ]),
            'recent_logins' => (clone $baseQuery)
                ->ofType('login')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'ip_address', 'device_type', 'browser', 'country', 'city', 'created_at']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get a specific activity log (admins can see any log, users only their own)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $query = $user->isAdmin() 
            ? ActivityLog::query()
            : ActivityLog::forUser($user->id);
        
        $log = $query->with('user:id,name,email')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }
}
