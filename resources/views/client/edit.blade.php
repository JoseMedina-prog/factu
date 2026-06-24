@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<x-form-layout
    title="Editar Cliente"
    :subtitle="$client->name"
    :back="route('clients.index')"
    icon="users"
    :avatar-initials="strtoupper(mb_substr($client->name, 0, 2))"
    avatar-source="name"
    :action="route('clients.update', $client)"
    method="PUT"
    submit-label="Guardar cambios"
    :cancel-href="route('clients.index')"
    footer-hint="Los cambios se aplican al guardar"
    :tips="[
        'Edita el <strong>nombre comercial</strong> si el cliente es una empresa.',
        'El <strong>NIT</strong> se usa para facturación electrónica ante la DIAN.',
        'Si desactivas el cliente, no podrá recibir nuevas facturas.',
        'Los cambios son reversibles en cualquier momento.',
    ]"
>
    <div class="field-primary">
        <label for="field-name" class="field-label">
            <span class="field-label-text">Nombre del cliente</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern input-modern-lg">
            <span class="input-modern-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <input type="text" id="field-name" name="name" value="{{ old('name', $client->name) }}" placeholder="Ej: Distribuidora El Sol S.A.S" required autocomplete="name" class="input-modern-field">
        </div>
        @error('name')
            <p class="field-error">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-document" class="field-label">Documento / NIT</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <input type="text" id="field-document" name="document" value="{{ old('document', $client->document) }}" placeholder="901234567-8" class="input-modern-field">
            </div>
        </div>

        <div class="field-group">
            <label for="field-email" class="field-label">Correo electrónico</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <input type="email" id="field-email" name="email" value="{{ old('email', $client->email) }}" placeholder="cliente@empresa.com" autocomplete="email" class="input-modern-field">
            </div>
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
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
                <input type="tel" id="field-phone" name="phone" value="{{ old('phone', $client->phone) }}" placeholder="300 123 4567" autocomplete="tel" class="input-modern-field">
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
                <input type="text" id="field-address" name="address" value="{{ old('address', $client->address) }}" placeholder="Calle 100 #15-20, Bogotá" autocomplete="street-address" class="input-modern-field">
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
                <p class="toggle-title">Cliente {{ $client->is_active ? 'activo' : 'inactivo' }}</p>
                <p class="toggle-description">{{ $client->is_active ? 'Podrá recibir facturas y aparecer en el listado' : 'No podrá recibir facturas hasta activarlo' }}</p>
            </div>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }}>
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
    </div>
</x-form-layout>
@endsection
