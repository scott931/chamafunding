# Create Test Campaign - Quick Steps

## The Issue
You're seeing "No campaigns yet" because there are **no campaigns in the database**.

## Quick Solution

### Step 1: Run the Test Campaign Seeder

I've created a seeder file for you. Run this command:

```bash
php artisan db:seed --class=TestCampaignSeeder
```

This will create a test campaign that is **immediately visible** (status: active).

### Step 2: Refresh Your Browser

Go to `/crowdfundings` and you should now see the test campaign!

## Alternative: Create Campaign via Tinker

If you prefer to create a campaign manually:

```bash
php artisan tinker
```

Then copy-paste this:

```php
$admin = App\Models\User::where('email', 'admin@example.com')->first() ?? App\Models\User::first();
$campaign = App\Models\Campaign::create([
    'title' => 'My First Campaign',
    'slug' => 'my-first-campaign-' . time(),
    'category' => 'project',
    'description' => 'This is a detailed campaign description that meets all validation requirements.',
    'created_by' => $admin->id,
    'goal_amount' => 5000 * 100,
    'raised_amount' => 0,
    'currency' => 'USD',
    'status' => 'active',
    'starts_at' => now(),
    'ends_at' => now()->addDays(60),
    'deadline' => now()->addDays(60),
]);
echo "Campaign created: {$campaign->title}\n";
```

## What Happens After Creating

- ✅ Campaign will appear at `/crowdfundings`
- ✅ Admin can see all campaigns (including drafts)
- ✅ Regular users see only active/successful/closed campaigns
- ✅ You can create more campaigns via API or database

## Create Campaign via API

```bash
POST /api/v1/campaigns
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "New Campaign",
  "category": "project",
  "description": "This campaign description has more than fifty characters as required.",
  "goal_amount": 5000,
  "currency": "USD",
  "deadline": "2024-12-31"
}
```

Then activate it:
```bash
POST /api/v1/campaigns/{id}/activate
```

## Verify Campaign Exists

```bash
php artisan tinker --execute="echo 'Total campaigns: ' . App\Models\Campaign::count();"
```

