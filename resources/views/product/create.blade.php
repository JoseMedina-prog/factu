@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')
<x-form-layout
    title="Nuevo Producto"
    subtitle="Agrega un producto o servicio a tu catálogo"
    :back="route('products.index')"
    icon="product"
    avatar-initials="PR"
    avatar-source="name"
    :action="route('products.store')"
    submit-label="Crear producto"
    :cancel-href="route('products.index')"
    footer-hint="El precio y el tipo son obligatorios para facturar"
    :tips="[
        'El <strong>nombre</strong> aparecerá tal cual en las facturas.',
        'Usa <strong>0%</strong> de impuesto para productos exentos.',
        'Los productos <strong>inactivos</strong> no aparecen al facturar.',
        'Podrás agregar descripciones largas más adelante.',
    ]"
>
    <div class="field-primary">
        <label for="field-name" class="field-label">
            <span class="field-label-text">Nombre del producto o servicio</span>
            <span class="field-label-required">Requerido</span>
        </label>
        <div class="input-modern input-modern-lg">
            <span class="input-modern-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </span>
            <input type="text" id="field-name" name="name" value="{{ old('name') }}" placeholder="Ej: Servicio de consultoría" required autofocus autocomplete="off" class="input-modern-field">
        </div>
        @error('name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-group">
        <label for="field-description" class="field-label">Descripción</label>
        <div class="input-modern items-start">
            <span class="input-modern-icon pt-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                </svg>
            </span>
            <textarea id="field-description" name="description" rows="3" placeholder="Describe brevemente este producto o servicio" class="input-modern-field resize-y min-h-[88px] py-3">{{ old('description') }}</textarea>
        </div>
    </div>

    <div class="field-grid-2">
        <div class="field-group">
            <label for="field-price" class="field-label">
                <span class="field-label-text">Precio</span>
                <span class="field-label-required">Requerido</span>
            </label>
            <div class="input-modern">
                <span class="input-modern-icon">
                    <span class="text-sm font-semibold">$</span>
                </span>
                <input type="number" id="field-price" name="price" value="{{ old('price') }}" step="0.01" min="0" placeholder="0.00" required class="input-modern-field">
            </div>
            @error('price')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field-group">
            <label for="field-tax" class="field-label">Impuesto (%)</label>
            <div class="input-modern">
                <input type="number" id="field-tax" name="tax" value="{{ old('tax', 0) }}" step="0.01" min="0" max="100" placeholder="19" class="input-modern-field">
                <span class="input-modern-icon right-0 left-auto">
                    <span class="text-sm font-semibold">%</span>
                </span>
            </div>
        </div>
    </div>

    <div class="field-group">
        <label for="field-type" class="field-label">Tipo de ítem</label>
        <div class="input-modern">
            <span class="input-modern-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </span>
            <select id="field-type" name="type" class="input-modern-field appearance-none pr-10 cursor-pointer">
                <option value="product" {{ old('type') == 'product' ? 'selected' : '' }}>Producto físico</option>
                <option value="service" {{ old('type') == 'service' ? 'selected' : '' }}>Servicio</option>
            </select>
            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
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
                <p class="toggle-title">Producto activo</p>
                <p class="toggle-description">Aparecerá disponible al crear facturas</p>
            </div>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
        </label>
    </div>
</x-form-layout>
@endsection
