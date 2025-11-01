# Quick Fix: Create Your First Campaign

## Issue
You're seeing "No campaigns yet" because there are **0 campaigns** in the database.

## Solution: Create a Campaign

### Option 1: Create via API (Quick Test)

**Step 1: Get your admin token**
```bash
# Login as admin to get token
POST /api/login
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Step 2: Create Campaign**
```bash
POST /api/v1/campaigns
Authorization: Bearer {your_admin_token}
Content-Type: application/json

{
  "title": "My First Campaign",
  "category": "project",
  "description": "This is a detailed description of my campaign. It needs at least 50 characters to be valid.",
  "goal_amount": 5000,
  "currency": "USD",
  "deadline": "2024-12-31"
}
```

**Step 3: Activate Campaign (Make it visible)**
```bash
POST /api/v1/campaigns/{campaign_id}/activate
Authorization: Bearer {your_admin_token}
```

### Option 2: Create via Database Seeder

Create a seeder to add test campaigns:

```php
// database/seeders/CampaignSeeder.php
use App\Models\Campaign;
use App\Models\User;

Campaign::create([
    'title' => 'Test Campaign',
    'slug' => 'test-campaign-' . time(),
    'category' => 'project',
    'description' => 'This is a test campaign description that is long enough to meet the requirements.',
    'created_by' => User::where('email', 'admin@example.com')->first()->id,
    'goal_amount' => 10000 * 100, // $10,000 in cents
    'raised_amount' => 0,
    'currency' => 'USD',
    'status' => 'active', // Set to active so it's visible
    'starts_at' => now(),
    'ends_at' => now()->addDays(30),
]);
```

Then run:
```bash
php artisan db:seed --class=CampaignSeeder
```

### Option 3: Create via Tinker (Fastest)

```bash
php artisan tinker
```

Then paste:
```php
use App\Models\Campaign;
use App\Models\User;

$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    $admin = User::first();
}

$campaign = Campaign::create([
    'title' => 'My First Campaign',
    'slug' => 'my-first-campaign-' . time(),
    'category' => 'project',
    'description' => 'This is a detailed description of my first campaign that meets all the requirements.',
    'created_by' => $admin->id,
    'goal_amount' => 5000 * 100, // $5,000 in cents
    'raised_amount' => 0,
    'currency' => 'USD',
    'status' => 'active', // Set to active immediately
    'starts_at' => now(),
    'ends_at' => now()->addDays(60),
]);

echo "Campaign created: {$campaign->title} (ID: {$campaign->id})\n";
```

## Verify Campaign Was Created

```bash
php artisan tinker --execute="echo App\Models\Campaign::count() . ' campaigns found';"
```

Should show at least 1 campaign.

## After Creating Campaign

1. **If created as 'draft'**: Activate it via `POST /api/v1/campaigns/{id}/activate`
2. **If created as 'active'**: It should appear immediately on the `/crowdfundings` page

## Route Information

- **Web route**: `/crowdfundings` (shows list view)
- **API route**: `/api/v1/campaigns` (JSON list)

Both routes will show campaigns if:
- Admin user: Sees all campaigns (including drafts)
- Regular user: Sees only active/successful/closed campaigns

