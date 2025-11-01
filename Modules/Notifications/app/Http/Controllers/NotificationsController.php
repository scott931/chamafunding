<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationsController extends Controller
{
    /**
     * Display a listing of notifications
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', Auth::id());

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by channel
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by read status
        if ($request->has('read')) {
            if ($request->read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * Send a notification
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:email,sms,push,in_app',
            'channel' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'type' => $request->type,
                'channel' => $request->channel,
                'title' => $request->title,
                'message' => $request->message,
                'data' => $request->data,
                'status' => 'pending',
            ]);

            // Send the notification based on type
            $this->sendNotification($notification);

            return response()->json([
                'success' => true,
                'data' => $notification,
                'message' => 'Notification sent successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'type' => $request->type
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified notification
     */
    public function show($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        // Mark as read when viewed
        if (!$notification->isRead()) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification retrieved successfully'
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'unread_count' => $count
            ],
            'message' => 'Unread count retrieved successfully'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get notification preferences
     */
    public function preferences(): JsonResponse
    {
        $preferences = UserPreference::getNotificationPreferences(Auth::id());

        return response()->json([
            'success' => true,
            'data' => $preferences,
            'message' => 'Notification preferences retrieved successfully'
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_campaign_updates' => 'boolean',
            'email_payment_notifications' => 'boolean',
            'email_savings_updates' => 'boolean',
            'sms_important_updates' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            UserPreference::setNotificationPreferences(Auth::id(), $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification history
     */
    public function history(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', Auth::id());

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Notification history retrieved successfully'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification based on type
     */
    private function sendNotification(Notification $notification): void
    {
        try {
            switch ($notification->type) {
                case 'email':
                    $this->sendEmailNotification($notification);
                    break;
                case 'sms':
                    $this->sendSmsNotification($notification);
                    break;
                case 'push':
                    $this->sendPushNotification($notification);
                    break;
                case 'in_app':
                    $this->sendInAppNotification($notification);
                    break;
            }

            $notification->markAsSent();

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'error' => $e->getMessage()
            ]);

            $notification->markAsFailed($e->getMessage());
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(Notification $notification): void
    {
        // Check if user has email notifications enabled for this channel
        $preferences = UserPreference::getNotificationPreferences($notification->user_id);

        if (!$this->isEmailChannelEnabled($notification->channel, $preferences)) {
            return;
        }

        // Here you would implement actual email sending
        // For now, we'll just log it
        Log::info('Email notification sent', [
            'user_id' => $notification->user_id,
            'title' => $notification->title,
            'channel' => $notification->channel
        ]);
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification(Notification $notification): void
    {
        // Check if user has SMS notifications enabled
        $preferences = UserPreference::getNotificationPreferences($notification->user_id);

        if (!$preferences['sms_important_updates']) {
            return;
        }

        // Here you would implement actual SMS sending
        // For now, we'll just log it
        Log::info('SMS notification sent', [
            'user_id' => $notification->user_id,
            'title' => $notification->title,
            'channel' => $notification->channel
        ]);
    }

    /**
     * Send push notification
     */
    private function sendPushNotification(Notification $notification): void
    {
        // Check if user has push notifications enabled
        $preferences = UserPreference::getNotificationPreferences($notification->user_id);

        if (!$preferences['push_notifications']) {
            return;
        }

        // Here you would implement actual push notification sending
        // For now, we'll just log it
        Log::info('Push notification sent', [
            'user_id' => $notification->user_id,
            'title' => $notification->title,
            'channel' => $notification->channel
        ]);
    }

    /**
     * Send in-app notification
     */
    private function sendInAppNotification(Notification $notification): void
    {
        // In-app notifications are always sent (stored in database)
        Log::info('In-app notification created', [
            'user_id' => $notification->user_id,
            'title' => $notification->title,
            'channel' => $notification->channel
        ]);
    }

    /**
     * Check if email channel is enabled
     */
    private function isEmailChannelEnabled(string $channel, array $preferences): bool
    {
        switch ($channel) {
            case 'campaign_update':
                return $preferences['email_campaign_updates'] ?? true;
            case 'payment_success':
            case 'payment_failed':
                return $preferences['email_payment_notifications'] ?? true;
            case 'savings_update':
                return $preferences['email_savings_updates'] ?? true;
            default:
                return true;
        }
    }
}
