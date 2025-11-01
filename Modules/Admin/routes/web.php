<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminController;
use Modules\Admin\App\Http\Controllers\UserRoleController;
use Modules\Admin\App\Http\Controllers\SettingsController;

// Only Treasurer, Secretary, Auditor can access Admin module
Route::middleware(['auth', 'verified', 'admin.role'])->group(function () {
    // Main Dashboard
    Route::get('admin', [AdminController::class, 'index'])->name('admin.index');

    // Campaign Management
    Route::get('admin/campaigns', [AdminController::class, 'campaigns'])->name('admin.campaigns.index');
    Route::get('admin/campaigns/{id}', [AdminController::class, 'showCampaign'])->name('admin.campaigns.show');
    Route::patch('admin/campaigns/{id}/status', [AdminController::class, 'updateCampaignStatus'])->name('admin.campaigns.update-status');

    // User Management
    Route::get('admin/users', [UserRoleController::class, 'index'])->name('admin.users.index');
    Route::patch('admin/users/{user}', [UserRoleController::class, 'update'])->name('admin.users.update');
    Route::get('admin/users/{id}', [AdminController::class, 'showUser'])->name('admin.users.show');

    // Financial Overview
    Route::get('admin/financial', [AdminController::class, 'financial'])->name('admin.financial.index');
    Route::get('admin/transactions', [AdminController::class, 'transactions'])->name('admin.transactions.index');

    // Support & Moderation (Placeholder routes)
    Route::get('admin/support', [AdminController::class, 'support'])->name('admin.support.index');
    Route::get('admin/reports', [AdminController::class, 'reports'])->name('admin.reports.index');

    // Settings (Comprehensive RBAC-based)
    Route::get('admin/settings', [SettingsController::class, 'index'])->name('admin.settings.index');

    // Platform Settings (Super Admin only)
    Route::get('admin/settings/platform', [SettingsController::class, 'platform'])->name('admin.settings.platform');
    Route::post('admin/settings/platform', [SettingsController::class, 'updatePlatform'])->name('admin.settings.platform.update');

    // Campaign Settings (Super Admin, Moderator)
    Route::get('admin/settings/campaigns', [SettingsController::class, 'campaigns'])->name('admin.settings.campaigns');
    Route::post('admin/settings/campaigns', [SettingsController::class, 'updateCampaigns'])->name('admin.settings.campaigns.update');

    // User Settings (Super Admin, Moderator)
    Route::get('admin/settings/users', [SettingsController::class, 'users'])->name('admin.settings.users');
    Route::post('admin/settings/users', [SettingsController::class, 'updateUsers'])->name('admin.settings.users.update');

    // Financial Settings (Super Admin, Financial Admin)
    Route::get('admin/settings/financial', [SettingsController::class, 'financial'])->name('admin.settings.financial');
    Route::post('admin/settings/financial', [SettingsController::class, 'updateFinancial'])->name('admin.settings.financial.update');

    // Communication Settings (Super Admin, Moderator)
    Route::get('admin/settings/communication', [SettingsController::class, 'communication'])->name('admin.settings.communication');
    Route::post('admin/settings/communication', [SettingsController::class, 'updateCommunication'])->name('admin.settings.communication.update');

    // Appearance Settings (Super Admin only)
    Route::get('admin/settings/appearance', [SettingsController::class, 'appearance'])->name('admin.settings.appearance');
    Route::post('admin/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('admin.settings.appearance.update');

    // Advanced Settings (Super Admin only)
    Route::get('admin/settings/advanced', [SettingsController::class, 'advanced'])->name('admin.settings.advanced');
    Route::post('admin/settings/advanced', [SettingsController::class, 'updateAdvanced'])->name('admin.settings.advanced.update');

    // Audit Log (Super Admin only)
    Route::get('admin/settings/audit-log', [SettingsController::class, 'auditLog'])->name('admin.settings.audit-log');
});
