<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class VendorPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Products
            'manage-products',
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',

            // Branches
            'manage-branches',
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',

            // Categories
            'view-categories',

            // Variants
            'view-variants',

            // Variant Requests
            'create-variant-requests',
            'view-variant-requests',

            // Category Requests
            'create-category-requests',
            'view-category-requests',

            // Plans
            'view-plans',
            'subscribe-plans',

            // Subscriptions
            'view-subscriptions',
            'cancel-subscriptions',

            // Vendor Users
            'manage-vendor-users',
            'view-vendor-users',
            'create-vendor-users',
            'edit-vendor-users',
            'delete-vendor-users',

            // Profile
            'edit-profile',

            // Dashboard
            'view-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }
}
