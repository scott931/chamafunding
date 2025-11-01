# Crowdfunding Platform API Documentation

## Overview

This document provides comprehensive API documentation for the crowdfunding platform. The API is organized into modules and follows RESTful conventions.

## Base URL

```
https://your-domain.com/api/v1
```

## Authentication

All API endpoints (except public ones) require authentication using Laravel Sanctum tokens.

### Headers
```
Authorization: Bearer {your-token}
Content-Type: application/json
Accept: application/json
```

## Modules

### 1. Crowdfunding Module

#### Campaigns

**List Campaigns**
```
GET /campaigns
```

Query Parameters:
- `status` - Filter by status (draft, active, successful, failed, closed)
- `category` - Filter by category (emergency, project, community, education, health, environment)
- `search` - Search by title or description
- `my_campaigns` - Show only user's campaigns (true/false)
- `sort_by` - Sort by field (created_at, goal_amount, raised_amount, deadline)
- `sort_order` - Sort order (asc, desc)
- `per_page` - Items per page (default: 15)

**Create Campaign**
```
POST /campaigns
```

Request Body:
```json
{
    "title": "Campaign Title",
    "category": "emergency",
    "description": "Campaign description (min 50 characters)",
    "goal_amount": 10000.00,
    "currency": "USD",
    "deadline": "2024-12-31",
    "starts_at": "2024-01-01",
    "ends_at": "2024-12-31"
}
```

**Get Campaign Details**
```
GET /campaigns/{id}
```

**Update Campaign**
```
PUT /campaigns/{id}
```

**Delete Campaign**
```
DELETE /campaigns/{id}
```

**Make Contribution**
```
POST /campaigns/{id}/contribute
```

Request Body:
```json
{
    "amount": 100.00,
    "currency": "USD",
    "payment_method": "card",
    "payment_processor": "stripe",
    "transaction_id": "txn_123456789"
}
```

**Get Campaign Contributions**
```
GET /campaigns/{id}/contributions
```

**Get Campaign Analytics**
```
GET /campaigns/{id}/analytics
```

**Search Campaigns**
```
GET /campaigns-search
```

Query Parameters:
- `q` - Search query
- `category` - Filter by category
- `min_amount` - Minimum goal amount
- `max_amount` - Maximum goal amount
- `status` - Filter by status

### 2. Payments Module

#### Payments

**List Payments**
```
GET /payments
```

Query Parameters:
- `type` - Filter by transaction type
- `status` - Filter by status
- `from_date` - Filter from date
- `to_date` - Filter to date
- `per_page` - Items per page

**Process Payment**
```
POST /payments
```

Request Body:
```json
{
    "amount": 100.00,
    "currency": "USD",
    "payment_method": "card",
    "payment_provider": "stripe",
    "description": "Payment description",
    "campaign_id": 1,
    "savings_account_id": 1,
    "external_transaction_id": "txn_123456789"
}
```

**Get Payment Details**
```
GET /payments/{id}
```

**Check Payment Status**
```
GET /payments/{id}/status
```

**Refund Payment**
```
POST /payments/{id}/refund
```

Request Body:
```json
{
    "amount": 50.00,
    "reason": "Refund reason"
}
```

**Get Payment History**
```
GET /payments-history
```

#### Payment Methods

**Get Payment Methods**
```
GET /payment-methods
```

**Add Payment Method**
```
POST /payment-methods
```

Request Body:
```json
{
    "type": "card",
    "provider": "stripe",
    "external_id": "pm_123456789",
    "last_four": "4242",
    "brand": "visa",
    "exp_month": "12",
    "exp_year": "2025",
    "country": "US",
    "is_default": true
}
```

**Remove Payment Method**
```
DELETE /payment-methods/{id}
```

#### PayPal Integration

**Create PayPal Order**
```
POST /paypal/orders
```

Request Body:
```json
{
    "amount": 100.00,
    "currency": "USD",
    "description": "Payment description",
    "return_url": "https://your-domain.com/success",
    "cancel_url": "https://your-domain.com/cancel"
}
```

**Capture PayPal Order**
```
POST /paypal/orders/{order_id}/capture
```

**Get PayPal Order**
```
GET /paypal/orders/{order_id}
```

#### M-Pesa Integration

**Initiate M-Pesa Payment**
```
POST /mpesa/initiate-payment
```

Request Body:
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

**Query M-Pesa Transaction Status**
```
POST /mpesa/query-status
```

Request Body:
```json
{
    "checkout_request_id": "ws_CO_29012024123456789"
}
```

**Get M-Pesa Payment Methods**
```
GET /mpesa/payment-methods
```

**Add M-Pesa Payment Method**
```
POST /mpesa/payment-methods
```

Request Body:
```json
{
    "phone_number": "254712345678",
    "is_default": true
}
```

**Get Supported Countries**
```
GET /mpesa/supported-countries
```

### 3. Savings Module

#### Savings Accounts

**List Savings Accounts**
```
GET /savings-accounts
```

Query Parameters:
- `account_type` - Filter by account type (regular, fixed_deposit, goal_savings)
- `status` - Filter by status (active, inactive, suspended, closed)
- `per_page` - Items per page

**Create Savings Account**
```
POST /savings-accounts
```

Request Body:
```json
{
    "account_type": "regular",
    "interest_rate": 5.0,
    "currency": "USD",
    "minimum_balance": 100.00,
    "maximum_balance": 100000.00,
    "maturity_date": "2025-12-31",
    "notes": "Account notes"
}
```

**Get Savings Account Details**
```
GET /savings-accounts/{id}
```

**Update Savings Account**
```
PUT /savings-accounts/{id}
```

**Make Deposit**
```
POST /savings-accounts/{id}/deposit
```

Request Body:
```json
{
    "amount": 500.00,
    "description": "Deposit description"
}
```

**Make Withdrawal**
```
POST /savings-accounts/{id}/withdraw
```

Request Body:
```json
{
    "amount": 200.00,
    "description": "Withdrawal description"
}
```

**Calculate Interest**
```
GET /savings-accounts/{id}/calculate-interest
```

**Get Savings History**
```
GET /savings-accounts/{id}/history
```

Query Parameters:
- `transaction_type` - Filter by transaction type
- `from_date` - Filter from date
- `to_date` - Filter to date
- `per_page` - Items per page

**Get Savings Goals**
```
GET /savings-goals
```

**Close Savings Account**
```
POST /savings-accounts/{id}/close
```

### 4. Notifications Module

#### Notifications

**List Notifications**
```
GET /notifications
```

Query Parameters:
- `type` - Filter by type (email, sms, push, in_app)
- `channel` - Filter by channel
- `status` - Filter by status
- `read` - Filter by read status (true/false)
- `per_page` - Items per page

**Send Notification**
```
POST /notifications
```

Request Body:
```json
{
    "user_id": 1,
    "type": "email",
    "channel": "campaign_update",
    "title": "Notification Title",
    "message": "Notification message",
    "data": {
        "campaign_id": 1,
        "amount": 100.00
    }
}
```

**Get Notification Details**
```
GET /notifications/{id}
```

**Mark Notification as Read**
```
POST /notifications/{id}/mark-read
```

**Mark All Notifications as Read**
```
POST /notifications/mark-all-read
```

**Get Notification Preferences**
```
GET /notifications-preferences
```

**Update Notification Preferences**
```
PUT /notifications-preferences
```

Request Body:
```json
{
    "email_campaign_updates": true,
    "email_payment_notifications": true,
    "email_savings_updates": true,
    "sms_important_updates": false,
    "push_notifications": true
}
```

**Get Notification History**
```
GET /notifications-history
```

**Delete Notification**
```
DELETE /notifications/{id}
```

### 5. Finance Module

#### Financial Reports

**Get Financial Reports**
```
GET /finance/reports
```

Query Parameters:
- `from_date` - Start date (default: 30 days ago)
- `to_date` - End date (default: today)

**Get Transaction History**
```
GET /finance/transaction-history
```

Query Parameters:
- `transaction_type` - Filter by transaction type
- `status` - Filter by status
- `from_date` - Filter from date
- `to_date` - Filter to date
- `per_page` - Items per page

**Get Balance**
```
GET /finance/balance
```

**Calculate Fees**
```
POST /finance/calculate-fees
```

Request Body:
```json
{
    "amount": 100.00,
    "payment_method": "card",
    "currency": "USD"
}
```

**Calculate Interest**
```
POST /finance/calculate-interest
```

Request Body:
```json
{
    "principal": 1000.00,
    "rate": 5.0,
    "time_period": 12,
    "time_unit": "months"
}
```

### 6. Reports Module

#### Reports

**Get Campaign Reports**
```
GET /reports/campaigns
```

Query Parameters:
- `from_date` - Start date (default: 30 days ago)
- `to_date` - End date (default: today)

**Get Financial Reports**
```
GET /reports/financial
```

Query Parameters:
- `from_date` - Start date (default: 30 days ago)
- `to_date` - End date (default: today)

**Get User Reports**
```
GET /reports/users
```

Query Parameters:
- `from_date` - Start date (default: 30 days ago)
- `to_date` - End date (default: today)

**Get Analytics Data**
```
GET /reports/analytics
```

Query Parameters:
- `from_date` - Start date (default: 30 days ago)
- `to_date` - End date (default: today)

**Export Data**
```
POST /reports/export
```

Request Body:
```json
{
    "report_type": "campaigns",
    "format": "csv",
    "from_date": "2024-01-01",
    "to_date": "2024-12-31"
}
```

### 7. Subscriptions Module

#### Subscriptions

**List Subscriptions**
```
GET /subscriptions
```

Query Parameters:
- `status` - Filter by status
- `per_page` - Items per page

**Create Subscription**
```
POST /subscriptions
```

Request Body:
```json
{
    "price_id": "price_123456789",
    "payment_method": "pm_123456789",
    "quantity": 1
}
```

**Get Subscription Details**
```
GET /subscriptions/{id}
```

**Update Subscription**
```
PUT /subscriptions/{id}
```

Request Body:
```json
{
    "price_id": "price_123456789",
    "quantity": 2
}
```

**Cancel Subscription**
```
POST /subscriptions/{id}/cancel
```

**Resume Subscription**
```
POST /subscriptions/{id}/resume
```

**Get Subscription Status**
```
GET /subscriptions/{id}/status
```

**Get Billing History**
```
GET /subscriptions/{id}/billing-history
```

**Download Invoice**
```
GET /subscriptions/{id}/download-invoice/{invoice_id}
```

**Update Payment Method**
```
PUT /subscriptions/{id}/payment-method
```

Request Body:
```json
{
    "payment_method": "pm_123456789"
}
```

**Get Upcoming Invoice**
```
GET /subscriptions/{id}/upcoming-invoice
```

## Webhooks

### PayPal Webhook
```
POST /paypal/webhook
```

### Stripe Webhook
```
POST /stripe/webhook
```

### M-Pesa Webhook
```
POST /mpesa/webhook
```

## Error Responses

All error responses follow this format:

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

## Success Responses

All success responses follow this format:

```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Success message"
}
```

## Rate Limiting

API requests are rate limited to 1000 requests per hour per user.

## Pagination

Paginated responses include:

```json
{
    "success": true,
    "data": {
        "data": [...],
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "from": 1,
        "to": 15
    },
    "message": "Data retrieved successfully"
}
```

## Status Codes

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Environment Variables

Required environment variables:

```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# PayPal
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_WEBHOOK_ID=your_paypal_webhook_id

# Stripe
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

# M-Pesa
MPESA_CONSUMER_KEY=your_mpesa_consumer_key
MPESA_CONSUMER_SECRET=your_mpesa_consumer_secret
MPESA_SHORTCODE=your_mpesa_shortcode
MPESA_PASSKEY=your_mpesa_passkey
MPESA_ENVIRONMENT=sandbox
MPESA_CALLBACK_URL=your_callback_url

# Flutterwave
FLUTTERWAVE_PUBLIC_KEY=your_flutterwave_public_key
FLUTTERWAVE_SECRET_KEY=your_flutterwave_secret_key
FLUTTERWAVE_ENCRYPTION_KEY=your_flutterwave_encryption_key
FLUTTERWAVE_ENVIRONMENT=sandbox
```

## Testing

Use the provided test endpoints to verify API functionality:

```bash
# Test campaign creation
curl -X POST https://your-domain.com/api/v1/campaigns \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Campaign","category":"project","description":"Test description","goal_amount":1000,"currency":"USD"}'

# Test payment processing
curl -X POST https://your-domain.com/api/v1/payments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"amount":100,"currency":"USD","payment_method":"card","payment_provider":"stripe"}'
```
