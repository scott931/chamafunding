# PayPal Credentials Verification

## Current Status

### Hardcoded Test Credentials (Currently in Use)

**Location**: `Modules/Payments/app/Http/Controllers/PayPalController.php` (lines 37-40)

```php
Client ID: AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt
Client Secret: EDVoxL5U9u4v-Z5hZNFnE8Ss6wAYtq2hTA6Cqj8KvrBCoC5hJ8_ZoqITfhnaiBACRynyvnUKUsekhc8b
```

### Configuration Status

✅ **Backend Controller**: Uses these credentials as fallback when config/env not set
✅ **Frontend Button**: Uses Client ID from config/env or falls back to same hardcoded value
✅ **Mode**: Currently set to `sandbox` (default)
✅ **API Base URL**: `https://api-m.sandbox.paypal.com` (sandbox mode)

### Where These Credentials Are Used

1. **PayPalController::getAccessToken()** (line 37-40)
   - Used for backend API authentication
   - Gets OAuth token from PayPal

2. **show.blade.php** (line 161)
   - Used for frontend PayPal SDK
   - Loads PayPal JavaScript SDK with Client ID

### Integration Flow

```
Request Flow:
1. User clicks PayPal button
2. Frontend uses Client ID to load PayPal SDK
3. Frontend creates order → POST /api/v1/paypal/order
4. Backend uses Client ID + Secret to authenticate with PayPal
5. Backend gets OAuth token
6. Backend creates PayPal order
7. User approves payment
8. Frontend captures → POST /api/v1/paypal/capture
9. Backend captures payment
10. Contribution created
```

## To Use Your Own Credentials

### Option 1: Add to .env File (Recommended)

Create or update `.env` file:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_actual_client_id_here
PAYPAL_CLIENT_SECRET=your_actual_secret_here
PAYPAL_WEBHOOK_ID=your_webhook_id_here
```

Then run:
```bash
php artisan config:clear
```

### Option 2: Update config/services.php Directly

```php
'paypal' => [
    'mode' => 'sandbox',
    'client_id' => 'your_client_id',
    'client_secret' => 'your_secret',
],
```

## Verification

### Test Connection

You can test if the credentials work:

```bash
# Via Tinker
php artisan tinker
>>> $controller = new Modules\Payments\Http\Controllers\PayPalController();
>>> $reflection = new ReflectionClass($controller);
>>> $method = $reflection->getMethod('getAccessToken');
>>> $method->setAccessible(true);
>>> $token = $method->invoke($controller);
>>> echo $token ? 'SUCCESS' : 'FAILED';
```

### Test via API

Visit (while authenticated):
```
GET /api/v1/paypal/test
```

Expected response:
```json
{
    "status": "success",
    "message": "PayPal connection successful",
    "mode": "sandbox",
    "base_url": "https://api-m.sandbox.paypal.com"
}
```

## Security Notes

⚠️ **Important**:
- Hardcoded credentials are for **development/testing only**
- Never commit real production credentials to code
- Use `.env` file for production (and add `.env` to `.gitignore`)
- The current hardcoded credentials appear to be test/sandbox credentials

## Current Implementation Details

### Backend (PayPalController)
- ✅ Reads from `config('services.paypal.client_id')`
- ✅ Falls back to `env('PAYPAL_CLIENT_ID')`
- ✅ Final fallback to hardcoded test credentials
- ✅ Uses same logic for Client Secret

### Frontend (show.blade.php)
- ✅ Reads from `config('services.paypal.client_id')`
- ✅ Falls back to `env('PAYPAL_CLIENT_ID')`
- ✅ Final fallback to hardcoded test Client ID

Both frontend and backend will use your `.env` credentials if set, otherwise they'll use the hardcoded test credentials.

