<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'disconnected';
        }

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'database' => $dbStatus,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ]);
    }
}
