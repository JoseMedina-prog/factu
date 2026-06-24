@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
<x-form-layout
    title="Nuevo Usuario"
    subtitle="Crea un usuario para que acceda a tu empresa"
    :back="route('admin.users.index')"
    icon="user"
    avatar-initials="US"
    avatar-source="name"
    :action="route('admin.users.store')"
    submit-label="Crear usuario"
    :cancel-href="route('admin.users.index')"
    footer-hint="El usuario recibirá sus credenciales por correo"
    :tips="[
        'Usa el <strong>nombre real</strong> del usuario.',
        'Los <strong>administradores</strong> pueden gestionar todo.',
        'Los <strong>vendedores</strong> solo crean facturas.',
        'El usuario podrá cambiar su contraseña al ingresar.',
    ]"
>
    <div class="field-primary">
        <label for="field-name" class="field-label">
            <span class="field-label-text">Nombre completo</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern input-modern-lg">
            <span class="input-modern-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <input type="text" id="field-name" name="name" value="{{ old('name') }}" placeholder="Ej: Juan Pérez García" required autofocus autocomplete="name" class="input-modern-field">
        </div>
        @error('name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-group">
        <label for="field-email" class="field-label">
            <span class="field-label-text">Correo electrónico</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern">
            <span class="input-modern-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </span>
            <input type="email" id="field-email" name="email" value="{{ old('email') }}" placeholder="usuario@empresa.com" required autocomplete="email" class="input-modern-field">
        </div>
        @error('email')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-password" class="field-label">
                <span class="field-label-text">Contraseña</span>
                <span class="field-label-required">Requerido</span>
            </label>
            <div class="input-modern" x-data="{ show: false }">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <input :type="show ? 'text' : 'password'" id="field-password" name="password" placeholder="Mínimo 8 caracteres" required class="input-modern-field pr-12">
                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-primary-600 hover:bg-slate-50 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field-group">
            <label for="field-password-confirmation" class="field-label">
                <span class="field-label-text">Confirmar contraseña</span>
                <span class="field-label-required">Requerido</span>
            </label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </span>
                <input type="password" id="field-password-confirmation" name="password_confirmation" placeholder="Repite la contraseña" required class="input-modern-field">
            </div>
        </div>
    </div>

    <div class="field-group">
        <label for="field-tenant-id" class="field-label">
            <span class="field-label-text">Empresa</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern">
            <span class="input-modern-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </span>
            <select id="field-tenant-id" name="tenant_id" required class="input-modern-field appearance-none pr-10 cursor-pointer">
                <option value="">Seleccionar empresa</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" {{ old('tenant_id', $selectedTenant?->id) == $tenant->id ? 'selected' : '' }}>
                        {{ $tenant->name }} ({{ $tenant->nit }})
                    </option>
                @endforeach
            </select>
            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </div>
        @error('tenant_id')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-group">
        <label class="field-label">
            <span class="field-label-text">Rol</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="grid sm:grid-cols-2 gap-3">
            <label class="role-card">
                <input type="radio" name="role" value="admin" {{ old('role') == 'admin' ? 'checked' : '' }} class="sr-only peer">
                <div class="role-card-content">
                    <span class="role-icon role-icon-admin">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    <div>
                        <span class="role-title">Administrador</span>
                        <p class="role-description">Gestiona usuarios y datos</p>
                    </div>
                </div>
                <span class="role-check">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
            </label>
            <label class="role-card">
                <input type="radio" name="role" value="staff" {{ old('role') == 'staff' ? 'checked' : '' }} class="sr-only peer">
                <div class="role-card-content">
                    <span class="role-icon role-icon-staff">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <div>
                        <span class="role-title">Vendedor</span>
                        <p class="role-description">Crea facturas y clientes</p>
                    </div>
                </div>
                <span class="role-check">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
            </label>
        </div>
        @error('role')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
</x-form-layout>
@endsection
