# PayPal Integration Setup

This document outlines the setup required for the PayPal Checkout integration with Venmo support.

## Environment Variables

Add the following variables to your `.env` file:

```env
# PayPal Configuration
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_paypal_client_id_here
PAYPAL_CLIENT_SECRET=your_paypal_client_secret_here
PAYPAL_WEBHOOK_ID=your_paypal_webhook_id_here
```

## Getting PayPal Credentials

### 1. Create PayPal Developer Account
1. Go to [PayPal Developer](https://developer.paypal.com/)
2. Sign in with your PayPal account or create a new one
3. Navigate to "My Apps & Credentials"

### 2. Create Application
1. Click "Create App"
2. Choose "Default Application" or "Custom Application"
3. Select "Sandbox" for testing (or "Live" for production)
4. Note down the Client ID and Client Secret

### 3. Configure Webhooks (Optional)
1. In your PayPal app settings, go to "Webhooks"
2. Add webhook URL: `https://yourdomain.com/api/v1/paypal/webhook`
3. Select events: `CHECKOUT.ORDER.APPROVED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`
4. Note down the Webhook ID

## Testing

### Sandbox Testing
1. Set `PAYPAL_MODE=sandbox` in your `.env`
2. Use sandbox credentials
3. Test with PayPal sandbox buyer accounts
4. Visit `/checkout?amount=10.00&currency=USD&description=Test Payment`

### Live Testing
1. Set `PAYPAL_MODE=live` in your `.env`
2. Use live credentials
3. Test with real PayPal accounts

## Features Implemented

- ✅ PayPal Checkout integration
- ✅ Venmo support (when available)
- ✅ Order creation and capture
- ✅ Webhook handling
- ✅ Error handling and logging
- ✅ Responsive UI with Tailwind CSS
- ✅ CSRF protection
- ✅ Authentication integration

## API Endpoints

### Authenticated Routes (require auth:sanctum)
- `POST /api/v1/paypal/orders` - Create PayPal order
- `POST /api/v1/paypal/orders/{order_id}/capture` - Capture PayPal order
- `GET /api/v1/paypal/orders/{order_id}` - Get order details

### Public Routes
- `POST /api/v1/paypal/webhook` - PayPal webhook handler

### Web Routes
- `GET /checkout` - Checkout page
- `GET /checkout/success` - Success page

## Usage Examples

### Basic Checkout
```php
// Redirect to checkout page
return redirect()->route('checkout', [
    'amount' => 25.00,
    'currency' => 'USD',
    'description' => 'Campaign Contribution'
]);
```

### API Integration
```javascript
// Create order
const response = await fetch('/api/v1/paypal/orders', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        amount: 25.00,
        currency: 'USD',
        description: 'Campaign Contribution',
        return_url: 'https://yoursite.com/success',
        cancel_url: 'https://yoursite.com/cancel'
    })
});
```

## Security Notes

- All API endpoints use CSRF protection
- Webhook endpoints should verify PayPal signatures in production
- Store sensitive credentials securely
- Use HTTPS in production
- Implement proper error handling and logging

## Troubleshooting

### Common Issues
1. **Invalid credentials**: Verify your PayPal app credentials
2. **CORS errors**: Ensure your domain is whitelisted in PayPal app settings
3. **Webhook not receiving**: Check webhook URL and SSL certificate
4. **Venmo not showing**: Venmo availability depends on region and device

### Debug Mode
Enable debug logging by setting `LOG_LEVEL=debug` in your `.env` file. Check `storage/logs/laravel.log` for detailed PayPal API logs.
