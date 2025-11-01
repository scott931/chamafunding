<?php

namespace Modules\Admin\App\Services;

/**
 * Registry for managing settings categories and their role-based access permissions.
 */
class SettingsRegistry
{
    /**
     * Define which roles can access which settings categories.
     */
    public static function getCategoryPermissions(): array
    {
        return [
            'platform' => ['Super Admin'], // Platform & Business Model Settings
            'campaigns' => ['Super Admin', 'Moderator'], // Campaign Creation & Moderation
            'users' => ['Super Admin', 'Moderator'], // User & Security Settings
            'financial' => ['Super Admin', 'Financial Admin'], // Financial & Payment Settings
            'communication' => ['Super Admin', 'Moderator'], // Communication & Email
            'appearance' => ['Super Admin'], // Site & Appearance
            'advanced' => ['Super Admin'], // Advanced & Technical
        ];
    }

    /**
     * Get all accessible categories for the current user.
     */
    public static function getAccessibleCategories(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $accessible = [];
        $permissions = self::getCategoryPermissions();

        foreach ($permissions as $category => $roles) {
            if ($user->hasAnyRole($roles)) {
                $accessible[] = $category;
            }
        }

        return $accessible;
    }

    /**
     * Check if user can access a specific category.
     */
    public static function canAccess(string $category): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $permissions = self::getCategoryPermissions();
        $allowedRoles = $permissions[$category] ?? [];

        return $user->hasAnyRole($allowedRoles);
    }

    /**
     * Get category display names.
     */
    public static function getCategoryNames(): array
    {
        return [
            'platform' => 'Platform & Business Model',
            'campaigns' => 'Campaigns & Moderation',
            'users' => 'Users & Security',
            'financial' => 'Financial & Payments',
            'communication' => 'Communication & Email',
            'appearance' => 'Site & Appearance',
            'advanced' => 'Advanced & Technical',
        ];
    }
}
