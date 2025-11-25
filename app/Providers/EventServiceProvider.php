<?php

namespace App\Providers;

use App\Events\UserActivity;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Listeners\LogUserActivity;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserLoggedIn::class => [
            LogUserLogin::class,
        ],
        UserLoggedOut::class => [
            LogUserLogout::class,
        ],
        UserActivity::class => [
            LogUserActivity::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
