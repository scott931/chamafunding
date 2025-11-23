# Admin Abilities Summary

This document outlines all the abilities that admin users have that normal users do not have access to.

## Admin Roles

The following roles are considered "admin" roles and have access to admin features:
- **Super Admin** - Full access to all admin features
- **Financial Admin** - Access to financial settings and reports
- **Moderator** - Access to campaign moderation and user management
- **Support Agent** - Access to support and moderation features
- **Legacy Roles** (backward compatibility):
  - Treasurer
  - Secretary
  - Auditor

## Admin-Only Features

### 1. Admin Dashboard Access
- **Route**: `/admin`
- **Ability**: View comprehensive platform overview with:
  - Total money raised across all campaigns
  - Active campaigns count
  - Total campaigns count
  - Total backers (monthly and all-time)
  - Platform fees (monthly)
  - Contributions this month
  - Pending payouts
  - Open support tickets
  - Total users
  - New users this month
  - New campaigns this month
  - Funding trends over time
  - Growth data (campaigns and users)
  - Top performing categories
  - Recent critical activity
  - Campaigns by status breakdown
  - Recent large transactions ($500+)

### 2. Campaign Management
- **Routes**: 
  - `/admin/campaigns` - List all campaigns
  - `/admin/campaigns/{id}` - View campaign details
  - `/admin/campaigns/{id}/status` - Update campaign status (PATCH)
  - `/admin/campaigns/{id}` - Update campaign details (PUT)
- **Abilities**:
  - View ALL campaigns including drafts and failed campaigns (normal users can't see drafts)
  - Filter campaigns by status, category, search terms
  - View flagged/pending campaigns
  - Update campaign status (approve, suspend, etc.)
  - Update campaign details (title, category, description, goal amount, currency, dates, status)
  - View all contributions for any campaign
  - See campaign creator information

### 3. User Management
- **Routes**:
  - `/admin/users` - List all users
  - `/admin/users/{id}` - View user details
  - `/admin/users/{user}` - Update user role (PATCH)
- **Abilities**:
  - View all users in the system
  - View detailed user information including:
    - All campaigns created by user
    - All contributions made by user
    - Total amount contributed
    - Total amount raised
  - Assign/change user roles
  - View user account status

### 4. Financial Overview
- **Routes**:
  - `/admin/financial` - Financial dashboard
  - `/admin/transactions` - Transaction log
- **Abilities**:
  - View platform financial statistics:
    - Total fees (month, quarter, year)
    - Total transaction volume (month)
    - Fee revenue trends over time
  - View ALL transactions across the platform:
    - Filter by transaction type (payment, refund, transfer, interest, fee)
    - Filter by status (completed, pending, failed)
    - Search by reference, user name, or email
    - View transactions from all users (not just own)
    - See both FinancialTransaction and CampaignContribution records
  - View transaction details including:
    - User information
    - Campaign information
    - Payment method and provider
    - Amount, fees, net amount

### 5. Support & Moderation
- **Route**: `/admin/support`
- **Abilities**:
  - Access support center (placeholder for future implementation)
  - View reported content queue
  - Handle support tickets

### 6. Reports System
- **Routes**:
  - `/admin/reports` - Reports index
  - `/admin/reports/platform-overview` - Platform overview report
  - `/admin/reports/all-projects` - All projects report
  - `/admin/reports/financial-summary` - Financial summary report
  - `/admin/reports/backer-report` - Detailed backer report
  - `/admin/reports/user-management` - User management report
  - `/admin/reports/support-moderation` - Support & moderation report
- **Abilities**:
  - Generate comprehensive reports with filters
  - Export reports as PDF or CSV
  - View platform-wide analytics:
    - Total money pledged (all time and monthly)
    - Active and successful projects
    - Platform fees
    - New user registrations
  - View all projects with detailed information
  - Financial summaries with gross pledges, fees, and payouts
  - Backer reports with pledge details
  - User management reports with user types and activity
  - Support and moderation reports:
    - Projects pending review
    - Flagged projects
    - Failed payouts
    - Suspicious activity detection

### 7. Settings Management
- **Routes**:
  - `/admin/settings` - Settings index
  - `/admin/settings/platform` - Platform settings (Super Admin only)
  - `/admin/settings/campaigns` - Campaign settings (Super Admin, Moderator)
  - `/admin/settings/users` - User settings (Super Admin, Moderator)
  - `/admin/settings/financial` - Financial settings (Super Admin, Financial Admin)
  - `/admin/settings/communication` - Communication settings (Super Admin, Moderator)
  - `/admin/settings/appearance` - Appearance settings (Super Admin only)
  - `/admin/settings/advanced` - Advanced settings (Super Admin only)
  - `/admin/settings/audit-log` - Audit log (Super Admin only)
- **Abilities**:
  - **Platform Settings** (Super Admin only):
    - Configure funding models (all-or-nothing, keep-it-all, tipping)
    - Set fee structure (percentage, fixed, passthrough)
    - Configure payout threshold and schedule
    - Set base currency and supported currencies
    - Configure available countries
  - **Campaign Settings** (Super Admin, Moderator):
    - Set minimum/maximum funding goals
    - Set campaign duration limits
    - Configure required campaign elements (video, images, story)
    - Set approval workflow requirements
    - Configure content restrictions (prohibited categories, banned keywords)
    - Set manual review thresholds
  - **User Settings** (Super Admin, Moderator):
    - Configure registration settings (public signups, invite-only)
    - Set email verification requirements
    - Configure identity verification
    - Set security requirements (password length, 2FA)
    - Configure API access
    - Set session timeout
  - **Financial Settings** (Super Admin, Financial Admin):
    - Configure payment gateways (Stripe, PayPal)
    - Set gateway credentials
    - Configure payout methods
    - Set tax collection settings
    - Configure refund policies
  - **Communication Settings** (Super Admin, Moderator):
    - Configure notification preferences
    - Set up SMTP settings
    - Configure email templates
  - **Appearance Settings** (Super Admin only):
    - Set site name, logo, favicon
    - Configure landing page
    - Manage categories
  - **Advanced Settings** (Super Admin only):
    - Configure analytics (Google Analytics, Facebook Pixel)
    - Set custom scripts
    - Configure webhooks
    - View audit log of all settings changes

### 8. API Endpoints (Admin-Only)
- **Routes**:
  - `GET /api/v1/admin/dashboard-stats` - Dashboard statistics
  - `GET /api/v1/admin/payment-history` - Payment history (all users)
  - `GET /api/v1/admin/campaigns-count` - Total campaigns count
  - `GET /api/v1/admin/reports-available` - Available reports list
  - `GET /api/v1/admin/transaction-notifications` - Transaction notifications
  - `POST /api/v1/admin/notifications/{campaignId}/mark-read` - Mark notifications as read
  - `POST /api/v1/admin/notifications/mark-all-read` - Mark all notifications as read
- **Abilities**:
  - Access platform-wide statistics via API
  - View payment history for all users
  - Get transaction notifications grouped by campaign
  - Manage notification read status

### 9. Campaign Visibility
- **Admin Can See**:
  - ALL campaign statuses including:
    - `draft` campaigns (normal users cannot see these)
    - `pending` campaigns
    - `active` campaigns
    - `successful` campaigns
    - `failed` campaigns
    - `closed` campaigns
    - `suspended` campaigns
- **Normal Users Can Only See**:
  - `active` campaigns
  - `successful` campaigns
  - `closed` campaigns

### 10. Transaction Access
- **Admin Can**:
  - View transactions from ALL users
  - See all transaction types (payment, refund, transfer, interest, fee)
  - Filter and search across all transactions
  - View transaction details for any user
- **Normal Users Can Only**:
  - View their own transactions
  - See their own payment history

### 11. User Information Access
- **Admin Can**:
  - View any user's profile
  - See any user's campaigns
  - See any user's contributions
  - View any user's financial information
  - Access user account details
- **Normal Users Can Only**:
  - View their own profile
  - See their own campaigns
  - See their own contributions

### 12. Platform Analytics
- **Admin Has Access To**:
  - Platform-wide metrics and KPIs
  - Growth trends and analytics
  - Financial performance data
  - User engagement statistics
  - Campaign performance metrics
  - Category performance data
- **Normal Users Do Not Have Access To**:
  - Platform-wide analytics
  - Other users' data
  - Financial platform metrics

## Middleware Protection

All admin routes are protected by the `admin.role` middleware which checks if the user has one of the admin roles:
- Super Admin
- Financial Admin
- Moderator
- Support Agent
- Treasurer (legacy)
- Secretary (legacy)
- Auditor (legacy)

If a normal user tries to access admin routes, they will receive a 403 Forbidden error.

## Settings Access Control

Different admin roles have different levels of access to settings:

| Setting Category | Super Admin | Financial Admin | Moderator | Support Agent |
|-----------------|-------------|----------------|-----------|---------------|
| Platform | ✅ | ❌ | ❌ | ❌ |
| Campaigns | ✅ | ❌ | ✅ | ❌ |
| Users | ✅ | ❌ | ✅ | ❌ |
| Financial | ✅ | ✅ | ❌ | ❌ |
| Communication | ✅ | ❌ | ✅ | ❌ |
| Appearance | ✅ | ❌ | ❌ | ❌ |
| Advanced | ✅ | ❌ | ❌ | ❌ |

## Summary

Normal users (non-admin) are restricted from:
1. ❌ Admin dashboard access
2. ❌ Campaign management (can't see drafts, can't update status)
3. ❌ User management (can't view other users, can't change roles)
4. ❌ Financial overview (can't see platform-wide financial data)
5. ❌ Support & moderation tools
6. ❌ Reports system (can't generate platform-wide reports)
7. ❌ Settings management (can't modify platform settings)
8. ❌ Admin API endpoints
9. ❌ Viewing draft/pending campaigns
10. ❌ Viewing other users' transactions
11. ❌ Viewing other users' profiles and data
12. ❌ Platform-wide analytics

All of these features are exclusive to users with admin roles.

