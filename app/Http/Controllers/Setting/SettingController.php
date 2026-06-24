<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        return view('settings.index', compact('tenant'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            abort(403, 'No tienes una empresa asociada.');
        }

        $this->authorize('update', $tenant);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nit' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'default_tax_rate' => 'required|numeric|min:0|max:100',
            'invoice_prefix' => 'required|string|max:10',
            'credit_note_prefix' => 'required|string|max:10',
        ]);

        $tenant->update($validated);

        return redirect()->back()->with('success', 'Configuración actualizada correctamente');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            abort(403, 'No tienes una empresa asociada.');
        }

        $this->authorize('update', $tenant);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $path = $request->file('logo')->store('logos', 'public');
        $tenant->update(['logo_path' => $path]);

        return redirect()->back()->with('success', 'Logo actualizado correctamente');
    }
}
