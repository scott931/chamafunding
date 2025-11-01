# M-Pesa Integration Setup Guide

This guide will help you set up M-Pesa integration for your crowdfunding platform.

## Prerequisites

1. **M-Pesa Developer Account**: Register at [M-Pesa Developer Portal](https://business.m-pesa.com/developers/)
2. **Business Registration**: Ensure your business is registered in Kenya
3. **Valid Phone Number**: Kenyan phone number for testing

## Step 1: Register for M-Pesa Developer Account

1. Visit [M-Pesa Developer Portal](https://business.m-pesa.com/developers/)
2. Click "Get Started" and create an account
3. Complete the business verification process
4. Submit required documents (Business registration, KRA PIN, etc.)

## Step 2: Create an App

1. Log into the developer portal
2. Navigate to "My Apps" section
3. Click "Create App"
4. Fill in the required details:
   - App Name: Your crowdfunding platform name
   - App Description: Brief description of your platform
   - App Category: Financial Services
   - Callback URL: `https://your-domain.com/api/v1/mpesa/webhook`

## Step 3: Get API Credentials

After app approval, you'll receive:
- **Consumer Key**: Your app's consumer key
- **Consumer Secret**: Your app's consumer secret
- **Shortcode**: Your business shortcode
- **Passkey**: Your app's passkey

## Step 4: Configure Environment Variables

Add the following to your `.env` file:

```env
# M-Pesa Configuration
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here
MPESA_SHORTCODE=your_shortcode_here
MPESA_PASSKEY=your_passkey_here
MPESA_ENVIRONMENT=sandbox
MPESA_CALLBACK_URL=https://your-domain.com/api/v1/mpesa/webhook
```

## Step 5: Test the Integration

### Test Phone Numbers (Sandbox)

Use these test phone numbers for sandbox testing:
- **254708374149** - Success
- **254708374150** - User Cancelled
- **254708374151** - Insufficient Funds
- **254708374152** - Timeout

### Test the API

```bash
# Test M-Pesa payment initiation
curl -X POST https://your-domain.com/api/v1/mpesa/initiate-payment \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "254708374149",
    "amount": 100,
    "account_reference": "TEST001",
    "transaction_description": "Test payment"
  }'
```

## Step 6: Go Live

1. Complete testing in sandbox environment
2. Submit your app for production review
3. Once approved, update environment variables:
   ```env
   MPESA_ENVIRONMENT=live
   ```
4. Update callback URL to production URL

## API Endpoints

### Initiate Payment
```
POST /api/v1/mpesa/initiate-payment
```

**Request Body:**
```json
{
    "phone_number": "254712345678",
    "amount": 100.00,
    "account_reference": "CAMP001",
    "transaction_description": "Campaign contribution",
    "campaign_id": 1,
    "savings_account_id": 1
}
```

### Query Transaction Status
```
POST /api/v1/mpesa/query-status
```

**Request Body:**
```json
{
    "checkout_request_id": "ws_CO_29012024123456789"
}
```

### Get Payment Methods
```
GET /api/v1/mpesa/payment-methods
```

### Add Payment Method
```
POST /api/v1/mpesa/payment-methods
```

**Request Body:**
```json
{
    "phone_number": "254712345678",
    "is_default": true
}
```

### Get Supported Countries
```
GET /api/v1/mpesa/supported-countries
```

## Webhook Configuration

M-Pesa will send callbacks to your webhook URL for payment status updates. The webhook endpoint is:

```
POST /api/v1/mpesa/webhook
```

## Error Handling

The integration includes comprehensive error handling for:
- Invalid phone numbers
- Network timeouts
- API errors
- Payment failures
- Webhook processing errors

## Security Considerations

1. **HTTPS Required**: M-Pesa requires HTTPS for production
2. **Webhook Validation**: Validate webhook signatures (implement if needed)
3. **Rate Limiting**: Implement rate limiting for API calls
4. **Logging**: Log all M-Pesa transactions for audit purposes

## Testing Checklist

- [ ] Sandbox environment working
- [ ] Phone number validation working
- [ ] STK Push initiation working
- [ ] Webhook callbacks working
- [ ] Transaction status updates working
- [ ] Error handling working
- [ ] Payment method management working

## Support

For M-Pesa API support:
- [M-Pesa Developer Portal](https://business.m-pesa.com/developers/)
- [M-Pesa API Documentation](https://developer.safaricom.co.ke/)
- [M-Pesa Support](https://business.m-pesa.com/support)

## Common Issues

### 1. Invalid Phone Number
- Ensure phone number is in format: 254XXXXXXXXX
- Remove any spaces or special characters

### 2. STK Push Not Received
- Check if phone number is registered for M-Pesa
- Verify shortcode and passkey configuration
- Check network connectivity

### 3. Webhook Not Working
- Ensure webhook URL is accessible from internet
- Check if HTTPS is properly configured
- Verify webhook URL in M-Pesa developer portal

### 4. Transaction Fails
- Check account balance
- Verify transaction limits
- Check if account is active

## Fee Structure

M-Pesa charges vary by transaction amount:
- Up to 100 KES: ~1% fee
- 101-1,000 KES: ~0.8% fee
- Above 1,000 KES: ~0.5% fee

*Note: Fees are subject to change by Safaricom*
