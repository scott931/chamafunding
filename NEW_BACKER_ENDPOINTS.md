# New Backer Endpoints - Quick Reference

## All New Endpoints Added

### Project Discovery
1. **GET** `/api/v1/backer/recommendations`
   - Get personalized project recommendations based on backing history

2. **GET** `/api/v1/backer/trending`
   - Get trending campaigns

### Pledge Management
3. **PUT** `/api/v1/backer/pledges/{contributionId}/increase`
   - Increase existing pledge amount
   - Requires: `additional_amount` (integer, in cents)

4. **PUT** `/api/v1/backer/pledges/{contributionId}/change-tier`
   - Change reward tier for existing pledge
   - Requires: `reward_tier_id`

5. **POST** `/api/v1/backer/pledges/{contributionId}/survey`
   - Complete survey for a contribution
   - Requires: `responses` (array with question/answer pairs)

### Profile & Account
6. **GET** `/api/v1/backer/profile`
   - Get user profile information

7. **PUT** `/api/v1/backer/profile`
   - Update user profile
   - Optional: `name`, `phone`, `city`, `country`, `bio`

8. **GET** `/api/v1/backer/privacy-settings`
   - Get privacy settings

9. **PUT** `/api/v1/backer/privacy-settings`
   - Update privacy settings
   - Optional: `show_public_profile`, `show_backed_projects`, `show_email`, `show_phone` (all boolean)

10. **PUT** `/api/v1/backer/change-password`
    - Change password
    - Requires: `current_password`, `new_password`, `new_password_confirmation`

### Payment & Transactions
11. **GET** `/api/v1/backer/payment-methods`
    - Get saved payment methods

12. **GET** `/api/v1/backer/transactions/export`
    - Export transaction history
    - Optional filters: `status`, `from_date`, `to_date`

## Testing the Endpoints

### Using Postman or similar:
1. First authenticate to get a token
2. Use the token in `Authorization: Bearer {token}` header
3. All endpoints are under `/api/v1/backer/`

### Example cURL:
```bash
# Get recommendations
curl -X GET "http://localhost/api/v1/backer/recommendations" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Get profile
curl -X GET "http://localhost/api/v1/backer/profile" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example JavaScript (fetch):
```javascript
// Get recommendations
fetch('/api/v1/backer/recommendations', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => console.log(data));
```

## Verification Checklist

- ✅ Routes are registered in `Modules/Crowdfunding/routes/api.php`
- ✅ Controller methods exist in `BackerDashboardController.php`
- ✅ All methods require `auth:sanctum` middleware
- ✅ Route cache has been cleared
- ✅ Config cache has been cleared

## If Endpoints Still Don't Appear

1. **Check route registration:**
   ```bash
   php artisan route:list | grep backer
   ```

2. **Verify module is enabled:**
   - Check `modules_statuses.json` - Crowdfunding module should be enabled

3. **Clear all caches:**
   ```bash
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Check namespace:**
   - Controller namespace: `Modules\Crowdfunding\Http\Controllers`
   - Make sure autoload is updated: `composer dump-autoload`

5. **Restart server:**
   - If using `php artisan serve`, restart it

## Files Modified

- `Modules/Crowdfunding/routes/api.php` - Added new routes
- `Modules/Crowdfunding/app/Http/Controllers/BackerDashboardController.php` - Added new methods

