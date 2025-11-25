# Activity Logging System

This system tracks user activities, device information, and location data for security and analytics purposes.

## Features

- **Automatic Login/Logout Tracking**: All user logins and logouts are automatically logged with device and location information
- **Device Detection**: Automatically detects device type (desktop, mobile, tablet), browser, OS, and versions
- **Location Tracking**: Captures approximate location (country, city, region) based on IP address
- **Activity Logging**: Tracks significant user activities (page views, actions, etc.)
- **API Access**: RESTful API endpoints to view activity logs and statistics

## Database Schema

The `activity_logs` table stores:
- User ID (nullable for guest activities)
- Activity type (login, logout, page_view, etc.)
- IP address
- User agent
- Device information (type, name, browser, OS)
- Location information (country, city, region, coordinates)
- URL and HTTP method
- Additional metadata (JSON)

## Automatic Tracking

### Login/Logout Events
Login and logout events are automatically tracked when users authenticate via:
- Web login (`AuthenticatedSessionController`)
- API login (`AuthController`)

### General Activities
Use the `TrackUserActivity` middleware to automatically track user activities. Add it to your routes:

```php
Route::middleware(['auth', 'track.activity'])->group(function () {
    // Your routes
});
```

Or register it globally in `bootstrap/app.php`:

```php
$middleware->web(append: [
    \App\Http\Middleware\TrackUserActivity::class,
]);
```

## Manual Activity Logging

### Using the Trait

Add the `LogsActivity` trait to your controller:

```php
use App\Traits\LogsActivity;

class MyController extends Controller
{
    use LogsActivity;
    
    public function store(Request $request)
    {
        // Your logic
        
        // Log the activity
        $this->logActivity(
            'campaign_created',
            'Created a new campaign',
            ['campaign_id' => $campaign->id]
        );
    }
}
```

### Using Events Directly

```php
use App\Events\UserActivity;

event(new UserActivity(
    auth()->user(),
    'payment_made',
    'Made a payment',
    request(),
    ['amount' => 100, 'currency' => 'USD']
));
```

## API Endpoints

### Get Activity Logs
```
GET /api/v1/activity-logs
```

Query Parameters:
- `activity_type`: Filter by activity type
- `start_date`: Filter from date (YYYY-MM-DD)
- `end_date`: Filter to date (YYYY-MM-DD)
- `per_page`: Results per page (default: 20, max: 100)

Response:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "activity_type": "login",
        "description": "User logged in",
        "ip_address": "192.168.1.1",
        "device_type": "desktop",
        "browser": "Chrome",
        "os": "Windows",
        "country": "United States",
        "city": "New York",
        "created_at": "2025-11-25T12:00:00.000000Z"
      }
    ]
  }
}
```

### Get Activity Statistics
```
GET /api/v1/activity-logs/statistics
```

Response:
```json
{
  "success": true,
  "data": {
    "total_activities": 150,
    "activities_by_type": {
      "login": 25,
      "logout": 25,
      "page_view": 100
    },
    "devices_used": {
      "desktop": 100,
      "mobile": 40,
      "tablet": 10
    },
    "browsers_used": {
      "Chrome": 80,
      "Safari": 50,
      "Firefox": 20
    },
    "locations": [
      {
        "country": "United States",
        "city": "New York",
        "count": 50
      }
    ],
    "recent_logins": [...]
  }
}
```

### Get Specific Activity Log
```
GET /api/v1/activity-logs/{id}
```

## Device Information Service

The `DeviceInfoService` automatically extracts:
- **IP Address**: From request headers (X-Forwarded-For, X-Real-IP) or request IP
- **Device Type**: desktop, mobile, or tablet
- **Device Name**: iPhone, Android Phone, Windows PC, Mac, etc.
- **Browser**: Chrome, Safari, Firefox, Edge, Opera, etc.
- **Browser Version**: Extracted from user agent
- **Operating System**: Windows, macOS, Linux, iOS, Android, etc.
- **OS Version**: Extracted from user agent
- **Location**: Country, city, region, and coordinates (via IP geolocation)

## Location Service

Location information is obtained from IP addresses using the ip-api.com service (free tier: 45 requests/minute). For local/private IPs, location data is not captured.

## Privacy Considerations

- Location data is approximate (city-level accuracy)
- IP addresses are stored for security purposes
- Users can view their own activity logs
- Consider implementing data retention policies
- For GDPR compliance, consider adding user consent and data deletion features

## Performance

- Activity logging uses Laravel's queue system (ShouldQueue) to avoid blocking requests
- Location lookups are cached and timeout after 3 seconds
- The middleware filters out static assets and excluded routes

## Customization

### Excluding Routes from Tracking

Edit `app/Http/Middleware/TrackUserActivity.php`:

```php
protected $excludedRoutes = [
    'api/v1/health',
    'up',
    'your-route',
];
```

### Custom Activity Types

You can use any string as an activity type. Common examples:
- `login`, `logout`, `register`
- `page_view`, `dashboard_view`, `admin_page_view`
- `create`, `update`, `delete`
- `payment_made`, `campaign_created`, `profile_updated`

## Migration

Run the migration to create the activity_logs table:

```bash
php artisan migrate
```

## Testing Activity Logging

To test if activity logging is working, run:

```bash
php artisan activity:test
```

This command will:
- Check if the activity_logs table exists and is accessible
- Test direct log creation
- Test event dispatching
- Verify EventServiceProvider registration
- Show recent activity logs

## Troubleshooting

### No logs appearing after login/logout

1. **Check EventServiceProvider registration**: Ensure `App\Providers\EventServiceProvider::class` is in `bootstrap/providers.php`

2. **Verify events are dispatched**: Check that `event(new \App\Events\UserLoggedIn($user, $request))` is called in login controllers

3. **Check for errors**: Review Laravel logs at `storage/logs/laravel.log` for any exceptions

4. **Test manually**: Run `php artisan activity:test` to verify the system is working

5. **Check database**: Verify the migration ran: `php artisan migrate:status | grep activity_logs`

### Location data not appearing

Location lookups use ip-api.com (free tier: 45 requests/minute). If location data is missing:
- Local/private IPs won't have location data
- The service may be rate-limited
- Network issues may prevent API calls
- Location is optional and won't break logging if unavailable

