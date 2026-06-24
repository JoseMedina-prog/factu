<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('tenant')
            ->when($request->tenant_id, fn($q) => $q->where('tenant_id', $request->tenant_id))
            ->orderBy('name')
            ->paginate(15);

        $tenants = Tenant::orderBy('name')->get();

        return view('user.index', compact('users', 'tenants'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $tenants = Tenant::where('is_active', true)->orderBy('name')->get();
        $selectedTenant = $request->tenant_id ? Tenant::find($request->tenant_id) : null;

        return view('user.create', compact('tenants', 'selectedTenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'tenant_id' => 'required|exists:tenants,id',
            'role' => 'required|in:admin,staff',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'tenant_id' => $validated['tenant_id'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $tenants = Tenant::where('is_active', true)->orderBy('name')->get();

        return view('user.edit', compact('user', 'tenants'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'tenant_id' => 'required|exists:tenants,id',
            'role' => 'required|in:admin,staff',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'tenant_id' => $validated['tenant_id'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminarte a ti mismo');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente');
    }
}
