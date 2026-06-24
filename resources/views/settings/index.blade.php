@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<x-form-layout
    title="Configuración"
    subtitle="Personaliza tu empresa y preferencias de facturación"
    :back="route('dashboard')"
    icon="settings"
    avatar-initials="{{ strtoupper(mb_substr($tenant->name, 0, 2)) }}"
    avatar-source="name"
    :action="route('settings.update', $tenant)"
    method="PUT"
    submit-label="Guardar cambios"
    footer-hint="Los cambios aplican inmediatamente a toda tu empresa"
    :tips="[
        'El <strong>logo</strong> aparece en facturas y reportes.',
        'Los <strong>prefijos</strong> se usan al generar nuevos documentos.',
        'El <strong>IVA predeterminado</strong> se aplica a productos nuevos.',
        'Los cambios son reversibles en cualquier momento.',
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
            <input type="text" id="field-name" name="name" value="{{ old('name', $tenant->name) }}" required autocomplete="organization" class="input-modern-field">
        </div>
        @error('name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-nit" class="field-label">NIT</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <input type="text" id="field-nit" name="nit" value="{{ old('nit', $tenant->nit) }}" class="input-modern-field">
            </div>
        </div>

        <div class="field-group">
            <label for="field-email" class="field-label">Email</label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <input type="email" id="field-email" name="email" value="{{ old('email', $tenant->email) }}" autocomplete="email" class="input-modern-field">
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
                <input type="tel" id="field-phone" name="phone" value="{{ old('phone', $tenant->phone) }}" autocomplete="tel" class="input-modern-field">
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
                <input type="text" id="field-address" name="address" value="{{ old('address', $tenant->address) }}" autocomplete="street-address" class="input-modern-field">
            </div>
        </div>
    </div>

    <div class="field-group">
        <label class="field-label">
            <span class="field-label-text">Prefijos de facturación</span>
        </label>
        <div class="field-grid-2">
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $tenant->invoice_prefix ?? 'INV') }}" placeholder="INV" required class="input-modern-field">
            </div>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </span>
                <input type="text" name="credit_note_prefix" value="{{ old('credit_note_prefix', $tenant->credit_note_prefix ?? 'NC') }}" placeholder="NC" required class="input-modern-field">
            </div>
        </div>
        <p class="form-hint">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Ejemplos: INV-2024-0001, NC-2024-0001
        </p>
    </div>

    <div class="field-group">
        <label for="field-default-tax" class="field-label">IVA predeterminado (%)</label>
        <div class="input-modern max-w-xs">
            <input type="number" id="field-default-tax" name="default_tax_rate" value="{{ old('default_tax_rate', $tenant->default_tax_rate) }}" step="0.01" min="0" max="100" placeholder="19" required class="input-modern-field">
            <span class="input-modern-icon right-0 left-auto">
                <span class="text-sm font-semibold">%</span>
            </span>
        </div>
        <p class="form-hint">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Porcentaje aplicado a productos nuevos
        </p>
    </div>

    <div class="field-group">
        <label class="field-label">Logo de la empresa</label>
        <div class="flex items-center gap-5 p-4 bg-slate-50 rounded-xl border border-slate-200">
            <div class="w-20 h-20 bg-white rounded-xl flex items-center justify-center overflow-hidden border border-slate-200 flex-shrink-0">
                @if($tenant->logo_path)
                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo" class="w-full h-full object-contain">
                @else
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>
            <form action="{{ route('settings.logo', $tenant) }}" method="POST" enctype="multipart/form-data" class="flex-1">
                @csrf
                <label class="btn-modern btn-modern-ghost cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Subir logo
                    <input type="file" name="logo" class="hidden" accept="image/*" onchange="this.form.submit()">
                </label>
                <p class="text-xs text-slate-500 mt-2">PNG, JPG hasta 2MB. Recomendado: 200x200px</p>
            </form>
        </div>
    </div>
</x-form-layout>
@endsection
