<?php

namespace App\Traits;

use App\Events\UserActivity;
use Illuminate\Http\Request;

trait LogsActivity
{
    /**
     * Log a user activity
     */
    protected function logActivity(
        string $activityType,
        ?string $description = null,
        ?array $metadata = null,
        ?Request $request = null
    ): void {
        event(new UserActivity(
            auth()->user(),
            $activityType,
            $description,
            $request ?? request(),
            $metadata
        ));
    }
}

