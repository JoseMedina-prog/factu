<?php

namespace App\Services;

use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    public function create(StoreTenantRequest $request): Tenant
    {
        return DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->name,
                'nit' => $request->nit,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'is_active' => $request->is_active ?? true,
            ]);

            User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'tenant_id' => $tenant->id,
                'role' => 'admin',
            ]);

            return $tenant;
        });
    }

    public function update(Tenant $tenant, UpdateTenantRequest $request): Tenant
    {
        return DB::transaction(function () use ($tenant, $request) {
            $tenant->update($request->validated());
            return $tenant->fresh();
        });
    }

    public function delete(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            return $tenant->delete();
        });
    }

    public function getActiveTenants()
    {
        return Tenant::where('is_active', true)->orderBy('name')->get();
    }
}
