<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Articles
            'articles.view', 'articles.create', 'articles.edit', 'articles.delete',
            'articles.import',
            // QR Codes
            'qrcodes.view', 'qrcodes.print',
            // Stock
            'stock.view', 'stock.adjust', 'stock.transfer',
            // Sales
            'sales.view', 'sales.create', 'sales.cancel',
            'sales.discount',
            // Customers
            'customers.view', 'customers.create', 'customers.edit',
            'customers.credit',
            // Suppliers
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            // Purchase Orders
            'purchases.view', 'purchases.create', 'purchases.receive',
            // Reports
            'reports.basic', 'reports.advanced',
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
            // Branches
            'branches.view', 'branches.create', 'branches.edit',
            // Settings
            'settings.view', 'settings.edit',
            // Subscription
            'subscription.view', 'subscription.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'proprietaire' => $permissions, // all permissions
            'admin_boutique' => [
                'articles.view', 'articles.create', 'articles.edit', 'articles.delete', 'articles.import',
                'qrcodes.view', 'qrcodes.print',
                'stock.view', 'stock.adjust', 'stock.transfer',
                'sales.view', 'sales.create', 'sales.cancel', 'sales.discount',
                'customers.view', 'customers.create', 'customers.edit', 'customers.credit',
                'suppliers.view', 'suppliers.create', 'suppliers.edit',
                'purchases.view', 'purchases.create', 'purchases.receive',
                'reports.basic', 'reports.advanced',
                'users.view', 'users.create', 'users.edit',
                'branches.view',
                'settings.view',
            ],
            'responsable_succursale' => [
                'articles.view', 'articles.create', 'articles.edit',
                'qrcodes.view', 'qrcodes.print',
                'stock.view', 'stock.adjust', 'stock.transfer',
                'sales.view', 'sales.create', 'sales.discount',
                'customers.view', 'customers.create', 'customers.edit',
                'reports.basic',
                'users.view',
            ],
            'gestionnaire_stock' => [
                'articles.view', 'articles.create', 'articles.edit',
                'qrcodes.view',
                'stock.view', 'stock.adjust', 'stock.transfer',
                'suppliers.view', 'suppliers.create',
                'purchases.view', 'purchases.create', 'purchases.receive',
                'reports.basic',
            ],
            'caissier' => [
                'articles.view',
                'qrcodes.view',
                'stock.view',
                'sales.view', 'sales.create',
                'customers.view', 'customers.create',
            ],
            'comptable' => [
                'articles.view',
                'stock.view',
                'sales.view',
                'customers.view',
                'reports.basic', 'reports.advanced',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
