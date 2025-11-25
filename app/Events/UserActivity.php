<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class UserActivity
{
    use Dispatchable, SerializesModels;

    public ?User $user;
    public string $activityType;
    public ?string $description;
    public Request $request;
    public ?array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ?User $user,
        string $activityType,
        ?string $description = null,
        Request $request = null,
        ?array $metadata = null
    ) {
        $this->user = $user;
        $this->activityType = $activityType;
        $this->description = $description;
        $this->request = $request ?? request();
        $this->metadata = $metadata;
    }
}
