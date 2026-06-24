@extends('layouts.app')

@section('title', 'Nuevo proveedor')

@section('content')
<x-form-layout
    title="Nuevo proveedor"
    subtitle="Registra un proveedor para empezar a registrar compras"
    :action="route('suppliers.store')"
    :back="route('suppliers.index')"
    submit-label="Crear proveedor"
    :cancel-href="route('suppliers.index')"
    :tips="[
        'El <strong>NIT</strong> debe coincidir con el registrado en la DIAN para que las retenciones apliquen correctamente.',
        'La <strong>cuenta bancaria</strong> es útil cuando necesitas pagar electrónicamente al proveedor.',
        'Puedes dejar campos opcionales vacíos y completarlos después.',
    ]"
>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-input name="name" label="Razón social / Nombre" required placeholder="Ej: Distribuidora XYZ S.A.S" />
        <x-input name="contact_name" label="Persona de contacto" placeholder="Ej: Juan Pérez" />
        <x-select
            name="document_type"
            label="Tipo de documento"
            required
            :options="['NIT' => 'NIT', 'CC' => 'Cédula', 'CE' => 'Cédula de extranjería']"
            :value="old('document_type', 'NIT')"
        />
        <x-input name="document" label="Número de documento" placeholder="900123456-1" />
        <x-input name="email" type="email" label="Email" placeholder="contacto@proveedor.com" />
        <x-input name="phone" label="Teléfono" placeholder="+57 300 000 0000" />
        <x-input name="address" label="Dirección" placeholder="Calle 100 #15-20" />
        <x-input name="city" label="Ciudad" placeholder="Bogotá" />
    </div>

    <h3 class="form-section-title">Información bancaria (opcional)</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-input name="bank_name" label="Banco" placeholder="Bancolombia" />
        <x-input name="bank_account" label="Número de cuenta" placeholder="123-456789-00" />
        <x-select
            name="bank_account_type"
            label="Tipo"
            :options="['savings' => 'Ahorros', 'checking' => 'Corriente']"
            placeholder="Seleccionar..."
        />
    </div>

    <h3 class="form-section-title">Notas</h3>
    <x-input name="notes" label="Notas internas" placeholder="Información adicional sobre el proveedor" />
</x-form-layout>
@endsection