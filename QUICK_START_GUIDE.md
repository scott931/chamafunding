# Quick Start: Testing New Backer Endpoints

## ✅ What Was Added

**12 new endpoints** have been added to the Backer Dashboard API:

### 1. Project Discovery
- `GET /api/v1/backer/recommendations` - Personalized recommendations
- `GET /api/v1/backer/trending` - Trending campaigns

### 2. Pledge Management
- `PUT /api/v1/backer/pledges/{id}/increase` - Increase pledge amount
- `PUT /api/v1/backer/pledges/{id}/change-tier` - Change reward tier
- `POST /api/v1/backer/pledges/{id}/survey` - Complete survey

### 3. Profile & Account
- `GET /api/v1/backer/profile` - Get profile
- `PUT /api/v1/backer/profile` - Update profile
- `GET /api/v1/backer/privacy-settings` - Get privacy settings
- `PUT /api/v1/backer/privacy-settings` - Update privacy settings
- `PUT /api/v1/backer/change-password` - Change password

### 4. Payments & Transactions
- `GET /api/v1/backer/payment-methods` - List payment methods
- `GET /api/v1/backer/transactions/export` - Export transactions

## 🧪 How to Test

### Step 1: Get Authentication Token
```bash
# Login first to get your token
curl -X POST "http://localhost/api/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### Step 2: Test an Endpoint
```bash
# Get recommendations
curl -X GET "http://localhost/api/v1/backer/recommendations" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# Get profile
curl -X GET "http://localhost/api/v1/backer/profile" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Step 3: Verify Routes Are Loaded
```bash
php artisan route:list | findstr backer
```

You should see all the new endpoints listed.

## 📝 Example Responses

### Recommendations Response:
```json
{
  "success": true,
  "data": {
    "recommendations": [
      {
        "id": 1,
        "title": "Campaign Title",
        "category": "Tech",
        "progress_percentage": 75.5,
        "reason": "Based on your interests"
      }
    ]
  }
}
```

### Profile Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "privacy": {
      "show_public_profile": false,
      "show_backed_projects": false
    }
  }
}
```

## 🔍 Troubleshooting

### If endpoints return 404:
1. ✅ Routes are registered in `Modules/Crowdfunding/routes/api.php`
2. ✅ Controller methods exist in `BackerDashboardController.php`
3. ✅ Caches have been cleared
4. ✅ Autoload has been regenerated

### Next Steps:
1. **Restart your development server** if using `php artisan serve`
2. **Check module status** - ensure Crowdfunding module is enabled
3. **Verify authentication** - make sure you're sending the Bearer token
4. **Check route prefix** - all routes are under `/api/v1/backer/`

## 📂 Files Changed

- ✅ `Modules/Crowdfunding/routes/api.php` - Routes added
- ✅ `Modules/Crowdfunding/app/Http/Controllers/BackerDashboardController.php` - 12 new methods added
- ✅ `BACKER_FEATURE_SPECIFICATION.md` - Full documentation
- ✅ `BACKER_IMPLEMENTATION_SUMMARY.md` - Implementation details

## 🎯 Quick Test Script

Save this as `test-backer-endpoints.php`:

```php
<?php
// Quick test - replace with your actual token
$token = 'YOUR_TOKEN_HERE';
$baseUrl = 'http://localhost/api/v1/backer';

$endpoints = [
    'recommendations',
    'trending',
    'profile',
    'privacy-settings',
    'payment-methods',
    'transactions/export'
];

foreach ($endpoints as $endpoint) {
    $ch = curl_init("$baseUrl/$endpoint");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$endpoint: Status $status\n";
    if ($status === 200) {
        echo "✅ Working\n";
    } else {
        echo "❌ Failed\n";
    }
}
```

Run with: `php test-backer-endpoints.php`

