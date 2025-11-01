<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('notifications', NotificationsController::class)->names('notifications');

    // Additional notification endpoints
    Route::get('notifications/unread-count', [NotificationsController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('notifications/{id}/mark-read', [NotificationsController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [NotificationsController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('notifications-preferences', [NotificationsController::class, 'preferences'])->name('notifications.preferences');
    Route::put('notifications-preferences', [NotificationsController::class, 'updatePreferences'])->name('notifications.update-preferences');
    Route::get('notifications-history', [NotificationsController::class, 'history'])->name('notifications.history');
});
