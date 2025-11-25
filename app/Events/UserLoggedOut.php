<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class UserLoggedOut
{
    use Dispatchable, SerializesModels;

    public User $user;
    public Request $request;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
