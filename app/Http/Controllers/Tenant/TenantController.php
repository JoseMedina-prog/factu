<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index(Request $request): View
    {
        $tenants = Tenant::orderBy('name')->paginate(15);
        return view('tenant.index', compact('tenants'));
    }

    public function create(Request $request): View
    {
        return view('tenant.create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $this->tenantService->create($request);
        return redirect()->route('admin.tenants.index')->with('success', 'Empresa creada correctamente');
    }

    public function edit(Request $request, Tenant $tenant): View
    {
        return view('tenant.edit', compact('tenant'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->tenantService->update($tenant, $request);
        return redirect()->route('admin.tenants.index')->with('success', 'Empresa actualizada correctamente');
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);
        $this->tenantService->delete($tenant);
        return redirect()->route('admin.tenants.index')->with('success', 'Empresa eliminada correctamente');
    }

    public function select(Request $request, Tenant $tenant): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->session()->put('selected_tenant_id', $tenant->id);
        return redirect()->back()->with('success', 'Empresa seleccionada');
    }
}
