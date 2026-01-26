<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'manage-users',
                'description' => 'Manage user accounts, assign roles, and control user permissions',
            ],
            [
                'name' => 'manage-settings',
                'description' => 'Configure system settings including OFX import, retention periods, and matching thresholds',
            ],
            [
                'name' => 'view-reports',
                'description' => 'Access financial reports, analytics dashboards, and spending insights',
            ],
            [
                'name' => 'manage-reconciliations',
                'description' => 'Create, update, and complete bank and credit card reconciliations',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }
    }
}
