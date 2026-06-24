@extends('layouts.app')

@section('title', 'Nueva Empresa')

@section('content')
<x-form-layout
    title="Nueva Empresa"
    subtitle="Registra una empresa y su administrador principal"
    :back="route('admin.tenants.index')"
    icon="users"
    avatar-initials="EM"
    avatar-source="name"
    :action="route('admin.tenants.store')"
    submit-label="Crear empresa"
    :cancel-href="route('admin.tenants.index')"
    footer-hint="El administrador podrá cambiar su contraseña al ingresar"
    :tips="[
        'Usa el <strong>nombre comercial</strong> o razón social.',
        'El <strong>NIT</strong> debe ser el registrado ante la DIAN.',
        'El <strong>administrador</strong> podrá gestionar todo.',
        'La contraseña debe tener mínimo 8 caracteres.',
    ]"
>
    <div class="field-primary">
        <label for="field-name" class="field-label">
            <span class="field-label-text">Nombre de la empresa</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern input-modern-lg">
            <span class="input-modern-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </span>
            <input type="text" id="field-name" name="name" value="{{ old('name') }}" placeholder="Ej: Empresa Demo S.A.S" required autofocus autocomplete="organization" class="input-modern-field">
        </div>
        @error('name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-nit" class="field-label">
                <span class="field-label-text">NIT</span>
                <span class="field-label-required">Requerido</span>
            </label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <input type="text" id="field-nit" name="nit" value="{{ old('nit') }}" placeholder="901234567-8" required class="input-modern-field">
            </div>
            @error('nit')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field-group">
            <label for="field-email" class="field-label">Email de la empresa</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <input type="email" id="field-email" name="email" value="{{ old('email') }}" placeholder="empresa@ejemplo.com" autocomplete="email" class="input-modern-field">
            </div>
        </div>
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-phone" class="field-label">Teléfono</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </span>
                <input type="tel" id="field-phone" name="phone" value="{{ old('phone') }}" placeholder="300 123 4567" autocomplete="tel" class="input-modern-field">
            </div>
        </div>

        <div class="field-group">
            <label for="field-address" class="field-label">Dirección</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                <input type="text" id="field-address" name="address" value="{{ old('address') }}" placeholder="Calle 100 #15-20" autocomplete="street-address" class="input-modern-field">
            </div>
        </div>
    </div>

    <div class="field-group">
        <label for="field-admin-name" class="field-label">
            <span class="field-label-text">Nombre del administrador</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern">
            <span class="input-modern-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <input type="text" id="field-admin-name" name="admin_name" value="{{ old('admin_name') }}" placeholder="Nombre del usuario administrador" required autocomplete="name" class="input-modern-field">
        </div>
        @error('admin_name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-group">
        <label for="field-admin-email" class="field-label">
            <span class="field-label-text">Email del administrador</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern">
            <span class="input-modern-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </span>
            <input type="email" id="field-admin-email" name="admin_email" value="{{ old('admin_email') }}" placeholder="admin@empresa.com" required autocomplete="email" class="input-modern-field">
        </div>
        @error('admin_email')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-admin-password" class="field-label">
                <span class="field-label-text">Contraseña</span>
                <span class="field-label-required">Requerido</span>
            </label>
            <div class="input-modern" x-data="{ show: false }">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <input :type="show ? 'text' : 'password'" id="field-admin-password" name="admin_password" placeholder="Mínimo 8 caracteres" required class="input-modern-field pr-12">
                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-primary-600 hover:bg-slate-50 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            @error('admin_password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field-group">
            <label for="field-admin-password-confirmation" class="field-label">
                <span class="field-label-text">Confirmar contraseña</span>
                <span class="field-label-required">Requerido</span>
            </label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </span>
                <input type="password" id="field-admin-password-confirmation" name="admin_password_confirmation" placeholder="Repite la contraseña" required class="input-modern-field">
            </div>
        </div>
    </div>

    <div class="field-toggle-card">
        <div class="toggle-info">
            <div class="toggle-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <p class="toggle-title">Empresa activa</p>
                <p class="toggle-description">Podrá acceder inmediatamente al sistema</p>
            </div>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
    </div>
</x-form-layout>
@endsection
