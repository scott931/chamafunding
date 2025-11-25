<?php

namespace App\Listeners;

use App\Events\UserActivity;
use App\Models\ActivityLog;
use App\Services\DeviceInfoService;
use Illuminate\Support\Facades\Log;

class LogUserActivity
{
    /**
     * Handle the event.
     */
    public function handle(UserActivity $event): void
    {
        try {
            $deviceInfo = app(DeviceInfoService::class)->getDeviceInfo($event->request);
            $location = $deviceInfo['location'] ?? [];

            ActivityLog::create([
                'user_id' => $event->user?->id,
                'activity_type' => $event->activityType,
                'description' => $event->description,
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
                'metadata' => $event->metadata,
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the application
            Log::error('Failed to log user activity', [
                'user_id' => $event->user?->id,
                'activity_type' => $event->activityType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
