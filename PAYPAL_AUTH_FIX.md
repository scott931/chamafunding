# PayPal Authentication Fix

## Issue
Getting "Unauthenticated" error when trying to create PayPal orders from the campaign page.

## Root Cause
API routes use the `api` middleware group which doesn't include session middleware, so web session authentication wasn't working.

## Solution Applied

### Updated Routes (`Modules/Payments/routes/api.php`)
Changed PayPal routes to use `web` middleware (which includes sessions) and `auth` middleware:

```php
Route::middleware(['web', 'auth'])->prefix('v1/paypal')->group(function () {
    Route::get('/test', [PayPalController::class, 'testConnection']);
    Route::post('/order', [PayPalController::class, 'createOrder']);
    Route::post('/capture', [PayPalController::class, 'captureOrder']);
});
```

### Why This Works
- `web` middleware group includes:
  - `StartSession` - Enables session handling
  - `ShareErrorsFromSession` - Shares session errors
  - `VerifyCsrfToken` - CSRF protection (frontend already sends CSRF token)
  - `SubstituteBindings` - Route model binding

- `auth` middleware checks if user is authenticated via web session

### Testing Steps
1. Clear route cache: `php artisan route:clear` ✅
2. Refresh the campaign page
3. Click PayPal button
4. Should now authenticate properly

### Frontend Integration
The frontend already:
- ✅ Sends CSRF token in headers: `X-CSRF-TOKEN`
- ✅ Uses `credentials: 'same-origin'` to send cookies
- ✅ Is authenticated (user logged in via web session)

### Expected Result
- No more "Unauthenticated" error
- PayPal order creation should work
- Payment flow should proceed normally

