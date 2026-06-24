<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionService::class)->syncPermissions();

        $tenant = Tenant::factory()->create([
            'name' => 'Empresa Demo',
            'nit' => '901234567-8',
            'email' => 'admin@demofactu.com',
            'plan' => Tenant::PLAN_BASIC,
            'plan_expires_at' => now()->addYear(),
        ]);

        $tenant->applyPlanDefaults();
        $tenant->save();

        app(NumberingService::class)->createDefaultRanges($tenant);

        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@demofactu.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);
        $admin->assignRole(PermissionService::ROLE_ADMIN);

        $staff = User::factory()->create([
            'name' => 'Vendedor',
            'email' => 'vendedor@demofactu.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'staff',
        ]);
        $staff->assignRole(PermissionService::ROLE_STAFF);

        Client::factory()->count(10)->create([
            'tenant_id' => $tenant->id,
        ]);

        Product::factory()->count(15)->create([
            'tenant_id' => $tenant->id,
        ]);
    }
}