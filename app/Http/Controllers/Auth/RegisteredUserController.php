<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
            'company_nit' => ['required', 'string', 'max:50', 'unique:tenants,nit'],
        ]);

        DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'nit' => $validated['company_nit'],
                'is_active' => true,
                'plan' => Tenant::PLAN_FREE,
                'plan_expires_at' => now()->addDays(30),
            ]);

            $tenant->applyPlanDefaults();
            $tenant->save();

            app(NumberingService::class)->createDefaultRanges($tenant);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'tenant_id' => $tenant->id,
                'role' => 'admin',
            ]);

            $permissionService = app(PermissionService::class);
            $permissionService->syncPermissions();

            $user->assignRole($permissionService->assignRoleByTenantRole('admin'));

            Auth::login($user);
        });

        return redirect()->route('dashboard');
    }
}
