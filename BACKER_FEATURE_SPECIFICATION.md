# Complete Feature Specification for Normal Users (Backers/Supporters)

## Overview
This document outlines all features that should be available to normal users (backers/supporters) with the "Member" role in the crowdfunding platform. This specification ensures users have a comprehensive, user-friendly experience while maintaining clear boundaries from administrative features.

---

## Core Principle
**Empowerment through Simplicity**: The interface should make it easy to support projects, track pledges, and feel connected to creators, without any complexity or administrative clutter.

---

## Feature Categories

### 1. Dashboard & Overview ✅ (Implemented)
**Status**: Fully implemented in `BackerDashboardController::dashboardSummary()`

- **Personalized Dashboard** upon login showing:
  - Recently backed projects ✅
  - Recent project updates from creators they follow ⚠️ (Partial - needs following feature)
  - Recommended projects based on their interests ⚠️ (Needs implementation)
  - Quick stats (Total backed, Number of projects supported) ✅

**Endpoints**:
- `GET /api/v1/backer/dashboard/summary` ✅

---

### 2. Project Discovery & Browsing ⚠️ (Partial)
**Status**: Basic browsing exists, needs enhancement

#### Browse & Search Projects:
- ✅ Category-based browsing (via `CrowdfundingController::index()`)
- ✅ Search bar with basic filters
- ⚠️ Enhanced filters (Category, Funding Status, Location) - needs improvement
- ⚠️ Sort options (Most Popular, Ending Soon, Newest, Most Funded) - partial

#### Project Recommendations:
- ✅ "Because you backed [X]" recommendations - **IMPLEMENTED** (`recommendations()`)
- ✅ Trending projects in interested categories - **IMPLEMENTED** (`trending()`)

#### Saved Projects:
- ✅ "Watchlist" functionality (`saveCampaign`, `unsaveCampaign`, `savedCampaigns`)
- ⚠️ Notifications when saved projects launch/end - **NEEDS IMPLEMENTATION**

**Endpoints**:
- `GET /api/v1/campaigns-search` ✅ (public)
- `GET /api/v1/backer/saved-campaigns` ✅
- `POST /api/v1/backer/save-campaign` ✅
- `DELETE /api/v1/backer/unsave-campaign/{campaignId}` ✅
- `GET /api/v1/backer/recommendations` ✅ **IMPLEMENTED**
- `GET /api/v1/backer/trending` ✅ **IMPLEMENTED**

---

### 3. Backing & Payment Features ⚠️ (Partial)
**Status**: Basic contribution exists, needs enhancement

#### Pledge Management:
- ✅ Back a project at various reward tiers (via `CrowdfundingController::contribute()`)
- ✅ Increase existing pledge amount - **IMPLEMENTED** (`increasePledge()`)
- ✅ Change reward tier before campaign ends - **IMPLEMENTED** (`changeRewardTier()`)
- ❌ Add-ons system (extra products/services) - **NEEDS IMPLEMENTATION**

#### Secure Checkout:
- ✅ Multiple payment methods (Stripe, PayPal, M-Pesa) - handled in Payments module
- ✅ Secure payment processing
- ✅ Pledge confirmation with receipt

#### Payment Security:
- ✅ View payment history (`transactionHistory`)
- ✅ Download receipts/invoices (`downloadReceipt`)
- ❌ Manage saved payment methods - **NEEDS IMPLEMENTATION**

**Endpoints**:
- `POST /api/v1/campaigns/{id}/contribute` ✅
- `GET /api/v1/backer/transactions` ✅
- `GET /api/v1/backer/transactions/{contributionId}/receipt` ✅
- `PUT /api/v1/backer/pledges/{contributionId}/increase` ✅ **IMPLEMENTED**
- `PUT /api/v1/backer/pledges/{contributionId}/change-tier` ✅ **IMPLEMENTED**
- `POST /api/v1/backer/pledges/{contributionId}/addons` ❌ **MISSING** (Low priority - can be added later)
- `GET /api/v1/backer/payment-methods` ✅ **IMPLEMENTED**
- `POST /api/v1/backer/payment-methods` ❌ **MISSING**
- `DELETE /api/v1/backer/payment-methods/{id}` ❌ **MISSING**

---

### 4. Project Engagement & Tracking ✅ (Mostly Implemented)
**Status**: Core features implemented, needs enhancement

#### Project Updates:
- ✅ Unified feed of all updates from backed projects (`updatesFeed`)
- ❌ Notification badges for unread updates - **NEEDS IMPLEMENTATION**
- ❌ Like/comment on updates - **NEEDS IMPLEMENTATION**

#### Progress Tracking:
- ✅ Visual progress bars for each backed project (in `myPledges`)
- ✅ Status indicators (in pledge details)
- ✅ Estimated delivery dates (in reward tier info)

#### Community Features:
- ❌ Comment on project pages - **NEEDS IMPLEMENTATION**
- ❌ Reply to other comments - **NEEDS IMPLEMENTATION**
- ❌ Like/follow other backers' comments - **NEEDS IMPLEMENTATION**
- ❌ Report inappropriate comments - **NEEDS IMPLEMENTATION**

**Endpoints**:
- `GET /api/v1/backer/updates` ✅
- `GET /api/v1/backer/pledges` ✅
- `GET /api/v1/backer/pledges/{contributionId}` ✅
- `POST /api/v1/backer/updates/{updateId}/like` ❌ **MISSING**
- `POST /api/v1/backer/projects/{campaignId}/comments` ❌ **MISSING**
- `POST /api/v1/backer/comments/{commentId}/reply` ❌ **MISSING**

---

### 5. Communication & Notifications ⚠️ (Partial)
**Status**: Basic notifications exist, needs enhancement

#### Notification Center:
- ⚠️ Project update notifications (infrastructure exists in Notifications module)
- ❌ Comment replies and mentions - **NEEDS IMPLEMENTATION**
- ❌ Funding milestone alerts - **NEEDS IMPLEMENTATION**
- ❌ Project success/failure notifications - **NEEDS IMPLEMENTATION**

#### Email Preferences:
- ❌ Control frequency of email updates - **NEEDS IMPLEMENTATION**
- ❌ Opt-in/out for different notification types - **NEEDS IMPLEMENTATION**
- ❌ Newsletter subscriptions - **NEEDS IMPLEMENTATION**

**Endpoints**:
- `GET /api/v1/backer/notifications` ⚠️ (may exist in Notifications module)
- `PUT /api/v1/backer/notifications/{id}/read` ⚠️ **CHECK**
- `GET /api/v1/backer/notification-preferences` ❌ **MISSING**
- `PUT /api/v1/backer/notification-preferences` ❌ **MISSING**

---

### 6. Profile & Account Management ✅ (Implemented)
**Status**: Not implemented

#### Public Profile (Optional):
- ❌ Display name and avatar
- ❌ Option to show backed projects publicly
- ❌ Bio/description section

#### Privacy Settings:
- ❌ Control what information is public
- ❌ Manage data sharing preferences
- ❌ Download personal data

#### Account Security:
- ❌ Change password - **MAY EXIST IN AUTH MODULE**
- ❌ Enable two-factor authentication
- ❌ Connected social accounts
- ❌ Session management (log out from all devices)

**Endpoints**:
- `GET /api/v1/backer/profile` ✅ **IMPLEMENTED**
- `PUT /api/v1/backer/profile` ✅ **IMPLEMENTED**
- `GET /api/v1/backer/privacy-settings` ✅ **IMPLEMENTED**
- `PUT /api/v1/backer/privacy-settings` ✅ **IMPLEMENTED**
- `POST /api/v1/backer/download-data` ❌ **MISSING** (Can be added as enhancement)
- `PUT /api/v1/backer/change-password` ✅ **IMPLEMENTED**
- `POST /api/v1/backer/sessions/logout-all` ❌ **MISSING** (Can use Laravel Sanctum token revocation)

---

### 7. Support & Creator Interaction ❌ (Missing)
**Status**: Not implemented

#### Direct Messaging:
- ❌ Contact creators of backed projects (with limitations to prevent spam)
- ❌ Support ticket system for issue resolution

#### Feedback System:
- ❌ Rate/review projects after completion
- ❌ Report projects that violate guidelines
- ❌ Provide platform feedback

**Endpoints**:
- `POST /api/v1/backer/messages/send` ❌ **MISSING**
- `GET /api/v1/backer/messages` ❌ **MISSING**
- `POST /api/v1/backer/projects/{campaignId}/review` ❌ **MISSING**
- `POST /api/v1/backer/projects/{campaignId}/report` ❌ **MISSING**

---

### 8. Reward Fulfillment ⚠️ (Partial)
**Status**: Basic functionality exists

#### Reward Management:
- ✅ View all rewards from backed projects (in `pledgeDetails`)
- ✅ Complete backer surveys (sizes, preferences, etc.) - **IMPLEMENTED** (`completeSurvey()`)
- ✅ Update shipping address for each project (`updateShippingAddress`)

#### Track Shipping:
- ✅ Track shipping status with tracking numbers (in `pledgeDetails`)

#### Digital Rewards:
- ⚠️ Download digital content immediately when available - **NEEDS CHECK**
- ⚠️ Access to exclusive update posts - **NEEDS CHECK**
- ❌ Digital badge/recognition on profile - **NEEDS IMPLEMENTATION**

**Endpoints**:
- `PUT /api/v1/backer/pledges/{contributionId}/shipping` ✅
- `POST /api/v1/backer/pledges/{contributionId}/survey` ✅ **IMPLEMENTED**
- `GET /api/v1/backer/rewards/digital/{rewardId}/download` ❌ **MISSING**

---

### 9. Social & Sharing Features ❌ (Missing)
**Status**: Not implemented

#### Social Integration:
- ❌ Share projects on social media
- ❌ Referral links with tracking
- ❌ Invite friends to back projects

#### Community Building:
- ❌ Follow other backers with similar interests
- ❌ Follow creators
- ❌ Join project-specific communities
- ❌ Participate in backer-only discussions

**Endpoints**:
- `GET /api/v1/backer/projects/{campaignId}/share-link` ❌ **MISSING**
- `GET /api/v1/backer/referral-link` ❌ **MISSING**
- `POST /api/v1/backer/creators/{creatorId}/follow` ❌ **MISSING**
- `DELETE /api/v1/backer/creators/{creatorId}/unfollow` ❌ **MISSING**
- `GET /api/v1/backer/following/creators` ❌ **MISSING**

---

### 10. Financial Management ⚠️ (Partial)
**Status**: Basic transaction history exists

#### Pledge History:
- ✅ Complete history of all pledges (`transactionHistory`)
- ✅ Filter by status (via query params)
- ✅ Export data for tax purposes - **IMPLEMENTED** (`exportTransactions()`)

#### Refund Requests:
- ❌ Request refunds (if platform policy allows) - **NEEDS IMPLEMENTATION**
- ❌ Track refund status - **NEEDS IMPLEMENTATION**
- ❌ View refund history - **NEEDS IMPLEMENTATION**

**Endpoints**:
- `GET /api/v1/backer/transactions` ✅
- `GET /api/v1/backer/transactions/export` ✅ **IMPLEMENTED**
- `POST /api/v1/backer/pledges/{contributionId}/request-refund` ❌ **MISSING**
- `GET /api/v1/backer/refunds` ❌ **MISSING**

---

## What a Normal User Should NOT See or Access

The following features should be **explicitly restricted** for normal users:

1. ❌ Admin Dashboard or any administrative controls
2. ❌ Creator Dashboard features (unless they also have creator role)
3. ❌ Platform analytics or business metrics
4. ❌ User moderation tools
5. ❌ Financial payout systems
6. ❌ Content approval workflows
7. ❌ System settings of any kind
8. ❌ Access to other users' personal information
9. ❌ Bulk email or marketing tools

**Implementation Note**: Use middleware `EnsureUserHasAdminRole` or role checks to restrict these features.

---

## Implementation Priority

### Phase 1: Core Enhancements (High Priority)
1. Project recommendations and trending
2. Survey completion
3. Notification preferences
4. Profile management basics

### Phase 2: Engagement Features (Medium Priority)
5. Comments and interactions
6. Following creators
7. Social sharing

### Phase 3: Advanced Features (Lower Priority)
8. Direct messaging
9. Reviews and ratings
10. Refund requests
11. Advanced financial exports

---

## Role-Based Access Control

Ensure all backer endpoints are protected with:
- Authentication: `auth:sanctum` middleware
- Role Verification: Ensure user has "Member" role or appropriate backer role
- Ownership Checks: Users can only access their own data (pledges, transactions, etc.)

```php
// Example middleware check
Route::middleware(['auth:sanctum', 'role:Member'])->group(function () {
    // Backer routes
});
```

---

## Notes

- ✅ = Implemented
- ⚠️ = Partially implemented or needs enhancement
- ❌ = Not implemented / Missing

