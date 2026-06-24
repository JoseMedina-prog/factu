@extends('layouts.app')

@section('title', 'Editar rango')

@section('content')
<x-page-header
    title="Editar rango de numeración"
    subtitle="Modifique los datos del rango autorizado"
    :back="route('settings.numbering.index')" />

<form action="{{ route('settings.numbering.update', $range) }}" method="POST">
    @csrf @method('PUT')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Información general</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label class="label label-required">Tipo de documento</label>
                        <select name="document_type" class="input" required>
                            <option value="invoice" @selected(old('document_type', $range->document_type) === 'invoice')>Factura de venta</option>
                            <option value="credit_note" @selected(old('document_type', $range->document_type) === 'credit_note')>Nota crédito</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Prefijo</label>
                            <input type="text" name="prefix" value="{{ old('prefix', $range->prefix) }}"
                                   class="input" required maxlength="10" pattern="[A-Za-z0-9]+">
                        </div>

                        <div class="form-group">
                            <label class="label">Estado</label>
                            <label class="flex items-center gap-2 mt-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $range->is_active))>
                                <span class="text-sm">Rango activo</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Número inicial (Desde)</label>
                            <input type="number" name="from_number" value="{{ old('from_number', $range->from_number) }}"
                                   class="input" required min="1">
                            <p class="text-xs text-slate-500 mt-1">Actual: {{ $range->current_number }} asignados</p>
                        </div>

                        <div class="form-group">
                            <label class="label label-required">Número final (Hasta)</label>
                            <input type="number" name="to_number" value="{{ old('to_number', $range->to_number) }}"
                                   class="input" required min="1">
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Resolución DIAN</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label class="label">Número de resolución</label>
                        <input type="text" name="resolution_number" value="{{ old('resolution_number', $range->resolution_number) }}"
                               class="input" maxlength="100">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Fecha de resolución</label>
                            <input type="date" name="resolution_date" value="{{ old('resolution_date', $range->resolution_date?->format('Y-m-d')) }}" class="input">
                        </div>

                        <div class="form-group">
                            <label class="label">Fecha de vencimiento</label>
                            <input type="date" name="expiration_date" value="{{ old('expiration_date', $range->expiration_date?->format('Y-m-d')) }}" class="input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Clave técnica</label>
                        <input type="text" name="technical_key" value="{{ old('technical_key', $range->technical_key) }}" class="input">
                    </div>

                    <div class="form-group">
                        <label class="label">Notas</label>
                        <textarea name="notes" rows="3" class="input">{{ old('notes', $range->notes) }}</textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <div class="p-6 space-y-3">
                    <button type="submit" class="btn btn-primary w-full">Guardar cambios</button>
                    <a href="{{ route('settings.numbering.index') }}" class="btn btn-outline w-full">Cancelar</a>
                </div>
            </x-card>
        </div>
    </div>
</form>
@endsection