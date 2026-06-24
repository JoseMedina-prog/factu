@extends('layouts.app')

@section('title', 'Editar proveedor')

@section('content')
<x-form-layout
    title="Editar proveedor"
    subtitle="Actualiza la información del proveedor"
    :action="route('suppliers.update', $supplier)"
    method="PUT"
    :back="route('suppliers.show', $supplier)"
    submit-label="Guardar cambios"
    :cancel-href="route('suppliers.show', $supplier)"
>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-input name="name" label="Razón social / Nombre" required :value="$supplier->name" />
        <x-input name="contact_name" label="Persona de contacto" :value="$supplier->contact_name" />
        <x-select
            name="document_type"
            label="Tipo de documento"
            required
            :options="['NIT' => 'NIT', 'CC' => 'Cédula', 'CE' => 'Cédula de extranjería']"
            :value="$supplier->document_type"
        />
        <x-input name="document" label="Número de documento" :value="$supplier->document" />
        <x-input name="email" type="email" label="Email" :value="$supplier->email" />
        <x-input name="phone" label="Teléfono" :value="$supplier->phone" />
        <x-input name="address" label="Dirección" :value="$supplier->address" />
        <x-input name="city" label="Ciudad" :value="$supplier->city" />
    </div>

    <h3 class="form-section-title">Información bancaria</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-input name="bank_name" label="Banco" :value="$supplier->bank_name" />
        <x-input name="bank_account" label="Número de cuenta" :value="$supplier->bank_account" />
        <x-select
            name="bank_account_type"
            label="Tipo"
            :options="['savings' => 'Ahorros', 'checking' => 'Corriente']"
            :value="$supplier->bank_account_type"
            placeholder="Seleccionar..."
        />
    </div>

    <h3 class="form-section-title">Notas</h3>
    <x-input name="notes" label="Notas internas" :value="$supplier->notes" />

    <div class="form-group">
        <label class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($supplier->is_active) class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
            <span class="text-sm font-medium text-slate-700">Proveedor activo</span>
        </label>
    </div>
</x-form-layout>
@endsection