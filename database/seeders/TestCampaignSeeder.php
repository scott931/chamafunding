<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestCampaignSeeder extends Seeder
{
    /**
     * Seed a test campaign for demonstration.
     */
    public function run(): void
    {
        // Find an admin user
        $admin = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor']);
        })->first();

        // If no admin found, use first user or create a test admin
        if (!$admin) {
            $admin = User::first();
            if (!$admin) {
                $this->command->error('No users found. Please run UserSeeder first.');
                return;
            }
        }

        // Check if test campaign already exists
        $existing = Campaign::where('title', 'Test Campaign - Welcome!')->first();
        if ($existing) {
            $this->command->info('Test campaign already exists. Skipping...');
            return;
        }

        // Create a test campaign
        $campaign = Campaign::create([
            'title' => 'Test Campaign - Welcome!',
            'slug' => 'test-campaign-welcome-' . time(),
            'category' => 'project',
            'description' => 'This is a test campaign created to demonstrate the crowdfunding platform. You can use this campaign to test all features including contributions, updates, and campaign management.',
            'created_by' => $admin->id,
            'goal_amount' => 10000 * 100, // $10,000 in cents
            'raised_amount' => 0,
            'currency' => 'USD',
            'status' => 'active', // Set to active so it's immediately visible
            'starts_at' => now(),
            'ends_at' => now()->addDays(60),
            'deadline' => now()->addDays(60),
        ]);

        $this->command->info("✅ Test campaign created successfully!");
        $this->command->info("   Title: {$campaign->title}");
        $this->command->info("   ID: {$campaign->id}");
        $this->command->info("   Status: {$campaign->status}");
        $this->command->info("   Created by: {$admin->name} ({$admin->email})");
        $this->command->info("\n   You can now view it at: /crowdfundings");
    }
}

