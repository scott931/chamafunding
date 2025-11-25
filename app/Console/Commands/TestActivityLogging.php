<?php

namespace App\Console\Commands;

use App\Events\UserActivity;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestActivityLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test activity logging system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Activity Logging System...');
        
        // Check if table exists
        try {
            $count = ActivityLog::count();
            $this->info("Current activity logs in database: {$count}");
        } catch (\Exception $e) {
            $this->error("Error accessing activity_logs table: " . $e->getMessage());
            return 1;
        }

        // Test creating a log directly
        $this->info("\n1. Testing direct ActivityLog creation...");
        try {
            $user = User::first();
            if (!$user) {
                $this->warn("No users found. Creating a test log without user...");
                $log = ActivityLog::create([
                    'activity_type' => 'test',
                    'description' => 'Direct test log',
                    'ip_address' => '127.0.0.1',
                ]);
                $this->info("✓ Direct log created: ID {$log->id}");
            } else {
                $log = ActivityLog::create([
                    'user_id' => $user->id,
                    'activity_type' => 'test',
                    'description' => 'Direct test log',
                    'ip_address' => '127.0.0.1',
                ]);
                $this->info("✓ Direct log created: ID {$log->id} for user {$user->email}");
            }
        } catch (\Exception $e) {
            $this->error("✗ Failed to create direct log: " . $e->getMessage());
            return 1;
        }

        // Test event dispatching
        $this->info("\n2. Testing event dispatching...");
        try {
            $user = User::first();
            if ($user) {
                $request = Request::create('/test', 'GET');
                $request->headers->set('User-Agent', 'Mozilla/5.0 (Test Browser)');
                
                event(new UserActivity($user, 'test_event', 'Test activity via event', $request));
                $this->info("✓ Event dispatched successfully");
                
                // Check if log was created
                $newCount = ActivityLog::count();
                if ($newCount > $count) {
                    $this->info("✓ Event listener created log (count: {$count} -> {$newCount})");
                } else {
                    $this->warn("⚠ Event dispatched but no new log found. Check EventServiceProvider registration.");
                }
            } else {
                $this->warn("No users found. Skipping event test.");
            }
        } catch (\Exception $e) {
            $this->error("✗ Failed to dispatch event: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }

        // Check EventServiceProvider
        $this->info("\n3. Checking EventServiceProvider registration...");
        $providers = config('app.providers', []);
        if (in_array(\App\Providers\EventServiceProvider::class, $providers)) {
            $this->info("✓ EventServiceProvider is registered");
        } else {
            $this->warn("⚠ EventServiceProvider may not be registered. Check bootstrap/providers.php");
        }

        // List recent logs
        $this->info("\n4. Recent activity logs:");
        $recentLogs = ActivityLog::orderBy('created_at', 'desc')->limit(5)->get();
        if ($recentLogs->isEmpty()) {
            $this->warn("No activity logs found.");
        } else {
            foreach ($recentLogs as $log) {
                $this->line("  - [{$log->created_at}] {$log->activity_type}: {$log->description} (User: {$log->user_id})");
            }
        }

        $this->info("\n✓ Test completed!");
        return 0;
    }
}
