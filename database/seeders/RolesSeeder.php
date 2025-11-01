<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // New Admin Roles (RBAC-based)
            'Super Admin',
            'Financial Admin',
            'Moderator',
            'Support Agent',
            // Existing Roles (kept for backward compatibility)
            'Treasurer',
            'Secretary',
            'Auditor',
            'Member',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }
}


