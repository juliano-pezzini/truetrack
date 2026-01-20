<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role with all permissions
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator with full system access']
        );

        // Create user role with basic permissions
        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            ['description' => 'Standard user with basic access']
        );

        // Assign all permissions to admin role
        $allPermissions = Permission::all();
        $adminRole->permissions()->syncWithoutDetaching($allPermissions);

        // Assign basic permissions to user role
        $userPermissions = Permission::whereIn('name', [
            'view-reports',
            'manage-reconciliations',
        ])->get();
        $userRole->permissions()->syncWithoutDetaching($userPermissions);
    }
}
