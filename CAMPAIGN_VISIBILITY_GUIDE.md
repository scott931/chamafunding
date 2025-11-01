# Campaign Visibility & Admin Creation Guide

## Overview
This guide explains how campaigns are created by admins and how they become visible to regular users.

## Campaign Creation Flow

### 1. Admin Creates Campaign
**Endpoint**: `POST /api/v1/campaigns`

**Required Fields**:
- `title` (string, max 255)
- `category` (string: emergency, project, community, education, health, environment)
- `description` (string, min 50)
- `goal_amount` (numeric, min 100)
- `currency` (string, 3 chars, e.g., "USD")
- `deadline` (optional, date after today)
- `starts_at` (optional, date >= today)
- `ends_at` (optional, date after starts_at)

**Response**: Campaign is created with `status: 'draft'`

**Who Can Create**:
- ✅ Super Admin
- ✅ Moderator
- ✅ Financial Admin
- ✅ Treasurer
- ✅ Secretary
- ✅ Auditor
- ❌ Regular users (Members) - they can only contribute

### 2. Campaign Statuses

- **draft** - Created but not visible to users (only admins see it)
- **active** - Visible to all users, accepting contributions
- **successful** - Reached goal, visible to users
- **failed** - Didn't reach goal
- **closed** - Manually closed, still visible

### 3. Activate Campaign (Make Visible to Users)
**Endpoint**: `POST /api/v1/campaigns/{id}/activate`

This changes the campaign status from `draft` to `active`, making it visible to all users.

**Who Can Activate**:
- ✅ Super Admin
- ✅ Moderator
- ✅ Financial Admin
- ✅ Treasurer
- ✅ Secretary
- ✅ Auditor

**Example Request**:
```bash
curl -X POST "http://localhost/api/v1/campaigns/1/activate" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Campaign Title",
    "status": "active",
    ...
  },
  "message": "Campaign activated successfully and is now visible to all users"
}
```

## Campaign Visibility Rules

### For Regular Users (Members):
- ✅ Can see: `active`, `successful`, `closed` campaigns
- ❌ Cannot see: `draft`, `failed` campaigns (unless specifically filtered)

### For Admins:
- ✅ Can see: ALL campaigns including `draft` and `failed`

### Public Routes (Unauthenticated):
- ✅ Can see: `active`, `successful`, `closed` campaigns only
- ❌ Cannot see: `draft` campaigns

## API Endpoints

### Create Campaign (Admin Only)
```
POST /api/v1/campaigns
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "New Campaign",
  "category": "project",
  "description": "This is a detailed description of the campaign...",
  "goal_amount": 10000,
  "currency": "USD",
  "deadline": "2024-12-31",
  "starts_at": "2024-01-01",
  "ends_at": "2024-12-31"
}
```

### Activate Campaign (Admin Only)
```
POST /api/v1/campaigns/{id}/activate
Authorization: Bearer {admin_token}
```

### View Campaigns (Users)
```
GET /api/v1/campaigns
Authorization: Bearer {user_token}  (optional - public routes available)

# Filters available:
?status=active
?category=project
?search=keyword
?sort_by=created_at
?sort_order=desc
```

### View Single Campaign (Users)
```
GET /api/v1/campaigns/{id}
Authorization: Bearer {user_token}  (optional - public routes available)

# Returns 404 if campaign is draft and user is not admin
```

## Workflow Example

1. **Admin logs in** → Gets admin token
2. **Admin creates campaign** → `POST /api/v1/campaigns` → Status: `draft`
3. **Admin reviews campaign** → Can see it in admin panel
4. **Admin activates campaign** → `POST /api/v1/campaigns/{id}/activate` → Status: `active`
5. **Users can now see campaign** → `GET /api/v1/campaigns` → Campaign appears in list
6. **Users can contribute** → `POST /api/v1/campaigns/{id}/contribute`

## Frontend Integration Tips

### Admin Dashboard:
- Show all campaigns with status badges
- Add "Activate" button for draft campaigns
- Filter by status to show drafts separately

### User Dashboard:
- Only show active/successful/closed campaigns
- No need to filter drafts (they won't appear)

### Campaign Details Page:
- Check if user is admin before showing draft campaigns
- Show "This campaign is not available" message for draft campaigns to regular users

## Testing

### Test as Admin:
```bash
# 1. Login as admin
curl -X POST "http://localhost/api/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# 2. Create campaign (returns draft)
curl -X POST "http://localhost/api/v1/campaigns" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Campaign",
    "category": "project",
    "description": "This is a test campaign description with enough words",
    "goal_amount": 5000,
    "currency": "USD"
  }'

# 3. Activate campaign
curl -X POST "http://localhost/api/v1/campaigns/1/activate" \
  -H "Authorization: Bearer {admin_token}"
```

### Test as Regular User:
```bash
# 1. Login as user
curl -X POST "http://localhost/api/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# 2. List campaigns (won't show drafts)
curl -X GET "http://localhost/api/v1/campaigns" \
  -H "Authorization: Bearer {user_token}"

# 3. Try to view draft campaign (should return 404)
curl -X GET "http://localhost/api/v1/campaigns/1" \
  -H "Authorization: Bearer {user_token}"
```

## Status Filtering Logic

The system automatically filters campaigns based on user role:

- **No status filter + Regular user** → Shows only `active`, `successful`, `closed`
- **No status filter + Admin** → Shows ALL campaigns
- **Status filter specified** → Shows campaigns matching that status (respects user permissions)

## Security Notes

- ✅ Regular users cannot create campaigns
- ✅ Regular users cannot activate campaigns
- ✅ Regular users cannot see draft campaigns
- ✅ All admin endpoints require authentication
- ✅ Admin endpoints check user roles

