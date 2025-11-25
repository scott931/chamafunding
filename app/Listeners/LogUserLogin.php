<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\ActivityLog;
use App\Services\DeviceInfoService;
use Illuminate\Support\Facades\Log;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        try {
            $deviceInfo = app(DeviceInfoService::class)->getDeviceInfo($event->request);
            $location = $deviceInfo['location'] ?? [];

            $log = ActivityLog::create([
                'user_id' => $event->user->id,
                'activity_type' => 'login',
                'description' => 'User logged in',
                'ip_address' => $deviceInfo['ip_address'] ?? null,
                'user_agent' => $deviceInfo['user_agent'] ?? null,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'browser' => $deviceInfo['browser'] ?? null,
                'browser_version' => $deviceInfo['browser_version'] ?? null,
                'os' => $deviceInfo['os'] ?? null,
                'os_version' => $deviceInfo['os_version'] ?? null,
                'country' => $location['country'] ?? null,
                'city' => $location['city'] ?? null,
                'region' => $location['region'] ?? null,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'url' => $event->request->fullUrl(),
                'method' => $event->request->method(),
                'metadata' => [
                    'login_method' => 'web',
                ],
            ]);

            Log::info('Activity log created', [
                'log_id' => $log->id,
                'user_id' => $event->user->id,
                'device_type' => $log->device_type,
                'browser' => $log->browser,
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the login process
            Log::error('Failed to log user login activity', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
