# Backer Dashboard API Documentation

## Overview

The Backer Dashboard API provides endpoints for users (backers/supporters) to manage their pledges, track campaigns they've backed, view updates, and manage their crowdfunding activities.

## Base URL

```
/api/v1/backer
```

All endpoints require authentication using Laravel Sanctum.

## Endpoints

### 1. Dashboard Summary

Get a quick overview of the user's backing activity.

**Endpoint:** `GET /dashboard/summary`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_pledged": "1250.00",
    "total_campaigns_backed": 5,
    "active_campaigns": 3,
    "pending_surveys": 1,
    "unread_updates": 7
  },
  "message": "Dashboard summary retrieved successfully"
}
```

### 2. My Pledges

Get a list of all campaigns the user has backed (pledged to).

**Endpoint:** `GET /pledges`

**Query Parameters:**
- `campaign_status` (optional) - Filter by campaign status (active, successful, failed, closed)
- `per_page` (optional) - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "pledges": [
      {
        "id": 1,
        "campaign": {
          "id": 10,
          "title": "Amazing Product Campaign",
          "slug": "amazing-product-campaign",
          "featured_image": "https://...",
          "status": "active",
          "progress_percentage": 75.5,
          "goal_amount": "10,000.00",
          "raised_amount": "7,550.00",
          "currency": "USD",
          "deadline": "2024-12-31"
        },
        "creator": {
          "id": 5,
          "name": "John Doe"
        },
        "pledge": {
          "amount": "100.00",
          "currency": "USD",
          "date": "2024-01-15 10:30:00"
        },
        "reward_tier": {
          "id": 3,
          "name": "Early Bird Special",
          "description": "Get the product early at a discounted price",
          "estimated_delivery": "Oct 2024"
        },
        "fulfillment": {
          "delivery_status": "pending",
          "survey_completed": false,
          "has_shipping_address": true,
          "tracking_number": null,
          "tracking_carrier": null,
          "shipped_at": null,
          "delivered_at": null
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42
    }
  },
  "message": "Pledges retrieved successfully"
}
```

### 3. Pledge Details

Get detailed information about a specific pledge.

**Endpoint:** `GET /pledges/{contributionId}`

**Response:**
```json
{
  "success": true,
  "data": {
    "contribution": {
      "id": 1,
      "amount": "100.00",
      "currency": "USD",
      "status": "succeeded",
      "payment_processor": "stripe",
      "transaction_id": "txn_123456",
      "created_at": "2024-01-15 10:30:00"
    },
    "campaign": {
      "id": 10,
      "title": "Amazing Product Campaign",
      "slug": "amazing-product-campaign",
      "description": "...",
      "featured_image": "https://...",
      "images": ["https://..."],
      "status": "active",
      "progress_percentage": 75.5,
      "goal_amount": "10,000.00",
      "raised_amount": "7,550.00",
      "currency": "USD",
      "deadline": "2024-12-31"
    },
    "creator": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "reward_tier": {
      "id": 3,
      "name": "Early Bird Special",
      "description": "...",
      "reward_type": "physical",
      "requires_shipping": true,
      "estimated_delivery": "Oct 2024"
    },
    "shipping": {
      "name": "Jane Smith",
      "address": "123 Main St",
      "city": "New York",
      "state": "NY",
      "country": "USA",
      "postal_code": "10001",
      "phone": "+1234567890",
      "full_address": "123 Main St, New York, NY, 10001, USA"
    },
    "survey": {
      "completed": false,
      "completed_at": null,
      "responses": null
    },
    "delivery": {
      "status": "pending",
      "tracking_number": null,
      "tracking_carrier": null,
      "shipped_at": null,
      "delivered_at": null
    },
    "digital_rewards": null
  },
  "message": "Pledge details retrieved successfully"
}
```

### 4. Update Shipping Address

Update the shipping address for a pledge that requires shipping.

**Endpoint:** `PUT /pledges/{contributionId}/shipping`

**Request Body:**
```json
{
  "shipping_name": "Jane Smith",
  "shipping_address": "123 Main St",
  "shipping_city": "New York",
  "shipping_state": "NY",
  "shipping_country": "USA",
  "shipping_postal_code": "10001",
  "shipping_phone": "+1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "shipping": {
      "name": "Jane Smith",
      "address": "123 Main St",
      "city": "New York",
      "state": "NY",
      "country": "USA",
      "postal_code": "10001",
      "phone": "+1234567890",
      "full_address": "123 Main St, New York, NY, 10001, USA"
    }
  },
  "message": "Shipping address updated successfully"
}
```

### 5. Updates Feed

Get a unified feed of updates from all campaigns the user has backed.

**Endpoint:** `GET /updates`

**Query Parameters:**
- `campaign_id` (optional) - Filter by specific campaign
- `per_page` (optional) - Items per page (default: 20)

**Response:**
```json
{
  "success": true,
  "data": {
    "updates": [
      {
        "id": 1,
        "campaign": {
          "id": 10,
          "title": "Amazing Product Campaign",
          "slug": "amazing-product-campaign",
          "featured_image": "https://..."
        },
        "title": "Production Update",
        "content": "We've started production...",
        "type": "update",
        "author": {
          "id": 5,
          "name": "John Doe"
        },
        "published_at": "2024-01-20 14:30:00",
        "published_at_human": "2 days ago"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 20,
      "total": 35
    }
  },
  "message": "Updates feed retrieved successfully"
}
```

### 6. Transaction History

Get a list of all transactions (contributions) made by the user.

**Endpoint:** `GET /transactions`

**Query Parameters:**
- `status` (optional) - Filter by status (pending, succeeded, failed, refunded)
- `from_date` (optional) - Filter from date (YYYY-MM-DD)
- `to_date` (optional) - Filter to date (YYYY-MM-DD)
- `per_page` (optional) - Items per page (default: 20)

**Response:**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 1,
        "transaction_id": "txn_123456",
        "campaign": {
          "id": 10,
          "title": "Amazing Product Campaign",
          "slug": "amazing-product-campaign"
        },
        "amount": "100.00",
        "currency": "USD",
        "payment_method": "Card",
        "status": "succeeded",
        "date": "2024-01-15 10:30:00",
        "date_human": "5 days ago"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 20,
      "total": 42
    }
  },
  "message": "Transaction history retrieved successfully"
}
```

### 7. Download Receipt

Get receipt information for a transaction.

**Endpoint:** `GET /transactions/{contributionId}/receipt`

**Response:**
```json
{
  "success": true,
  "data": {
    "receipt_number": "RCP-00000001",
    "date": "January 15, 2024",
    "time": "10:30:00",
    "transaction_id": "txn_123456",
    "user": {
      "name": "Jane Smith",
      "email": "jane@example.com"
    },
    "campaign": {
      "title": "Amazing Product Campaign",
      "creator": "John Doe"
    },
    "contribution": {
      "amount": "100.00",
      "currency": "USD",
      "payment_processor": "stripe",
      "reward_tier": "Early Bird Special"
    }
  },
  "message": "Receipt generated successfully"
}
```

### 8. Save Campaign

Save a campaign to the user's watchlist.

**Endpoint:** `POST /save-campaign`

**Request Body:**
```json
{
  "campaign_id": 10
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "campaign_id": 10,
    "saved_at": "2024-01-20 15:00:00"
  },
  "message": "Campaign saved successfully"
}
```

### 9. Unsave Campaign

Remove a campaign from the user's watchlist.

**Endpoint:** `DELETE /unsave-campaign/{campaignId}`

**Response:**
```json
{
  "success": true,
  "message": "Campaign removed from watchlist"
}
```

### 10. Saved Campaigns

Get all campaigns saved to the user's watchlist.

**Endpoint:** `GET /saved-campaigns`

**Query Parameters:**
- `per_page` (optional) - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "campaigns": [
      {
        "id": 10,
        "title": "Amazing Product Campaign",
        "slug": "amazing-product-campaign",
        "description": "...",
        "featured_image": "https://...",
        "status": "active",
        "progress_percentage": 75.5,
        "goal_amount": "10,000.00",
        "raised_amount": "7,550.00",
        "currency": "USD",
        "deadline": "2024-12-31",
        "creator": {
          "id": 5,
          "name": "John Doe"
        },
        "saved_at": "2024-01-20 15:00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 15,
      "total": 25
    }
  },
  "message": "Saved campaigns retrieved successfully"
}
```

## Error Responses

All endpoints may return the following error responses:

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "shipping_address": ["The shipping address field is required."]
  }
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**400 Bad Request:**
```json
{
  "success": false,
  "message": "Error message here"
}
```

