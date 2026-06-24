<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    public const ROLE_SUPER_ADMIN = 'super-admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_VIEWER = 'viewer';

    public const PERMISSIONS = [
        'clients.view',
        'clients.create',
        'clients.update',
        'clients.delete',

        'products.view',
        'products.create',
        'products.update',
        'products.delete',

        'invoices.view',
        'invoices.create',
        'invoices.update',
        'invoices.delete',
        'invoices.send',
        'invoices.cancel',

        'payments.view',
        'payments.create',
        'payments.confirm',
        'payments.cancel',
        'payments.delete',
        'payments.export',

        'accounts-receivable.view',

        'credit-notes.view',
        'credit-notes.create',
        'credit-notes.update',
        'credit-notes.delete',
        'credit-notes.approve',
        'credit-notes.cancel',

        'suppliers.view',
        'suppliers.create',
        'suppliers.update',
        'suppliers.delete',

        'purchases.view',
        'purchases.create',
        'purchases.update',
        'purchases.cancel',

        'payables.view',
        'payables.create',
        'payables.export',

        'reports.view',
        'reports.export',

        'settings.view',
        'settings.update',
        'settings.logo',
        'settings.numbering',
        'settings.view_plan',

        'users.manage',
        'tenant.manage',
    ];

    public function syncPermissions(): void
    {
        Artisan::call('cache:clear');

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => self::ROLE_ADMIN, 'guard_name' => 'web']);
        $admin->syncPermissions(self::PERMISSIONS);

        $staff = Role::firstOrCreate(['name' => self::ROLE_STAFF, 'guard_name' => 'web']);
        $staff->syncPermissions([
            'clients.view', 'clients.create', 'clients.update',
            'products.view', 'products.create', 'products.update',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.send',
            'credit-notes.view', 'credit-notes.create', 'credit-notes.update', 'credit-notes.approve',
            'payments.view', 'payments.create', 'payments.confirm',
            'accounts-receivable.view',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'purchases.view', 'purchases.create',
            'payables.view', 'payables.create',
            'reports.view', 'reports.export',
            'settings.view',
        ]);

        $viewer = Role::firstOrCreate(['name' => self::ROLE_VIEWER, 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'clients.view',
            'products.view',
            'invoices.view',
            'credit-notes.view',
            'payments.view',
            'accounts-receivable.view',
            'suppliers.view',
            'purchases.view',
            'payables.view',
            'reports.view',
        ]);
    }

    public function assignRoleByTenantRole(string $tenantRole): string
    {
        return match ($tenantRole) {
            'admin' => self::ROLE_ADMIN,
            'staff' => self::ROLE_STAFF,
            default => self::ROLE_STAFF,
        };
    }
}