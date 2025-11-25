<?php

namespace App\Console\Commands;

use App\Events\UserLoggedIn;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class CreateTestLoginLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:test-login {--user-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test login activity log with device and location data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("Creating test login log for user: {$user->email}");

        // Create a mock request with realistic user agent
        $request = Request::create('/login', 'POST', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Set realistic user agent headers
        $userAgents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ];

        $userAgent = $userAgents[array_rand($userAgents)];
        $request->headers->set('User-Agent', $userAgent);
        $request->headers->set('X-Forwarded-For', '8.8.8.8'); // Use a public IP for location testing

        // Dispatch the event to test the listener
        try {
            event(new UserLoggedIn($user, $request));
            $this->info("✓ Login event dispatched successfully");
            
            // Check if log was created
            $log = ActivityLog::where('user_id', $user->id)
                ->where('activity_type', 'login')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($log) {
                $this->info("✓ Activity log created: ID {$log->id}");
                $this->line("  Device Type: " . ($log->device_type ?? 'null'));
                $this->line("  Device Name: " . ($log->device_name ?? 'null'));
                $this->line("  Browser: " . ($log->browser ?? 'null'));
                $this->line("  OS: " . ($log->os ?? 'null'));
                $this->line("  IP: " . ($log->ip_address ?? 'null'));
                $this->line("  Country: " . ($log->country ?? 'null'));
                $this->line("  City: " . ($log->city ?? 'null'));
            } else {
                $this->warn("⚠ Event dispatched but no log found. Check EventServiceProvider.");
            }
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
