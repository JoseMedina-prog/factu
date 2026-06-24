@extends('layouts.app')

@section('title', 'Nuevo rango')

@section('content')
<x-page-header
    title="Nuevo rango de numeración"
    subtitle="Configure un rango autorizado por la DIAN"
    :back="route('settings.numbering.index')" />

<form action="{{ route('settings.numbering.store') }}" method="POST">
    @csrf
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
                            <option value="invoice" @selected(old('document_type') === 'invoice')>Factura de venta</option>
                            <option value="credit_note" @selected(old('document_type') === 'credit_note')>Nota crédito</option>
                        </select>
                        @error('document_type') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Prefijo</label>
                            <input type="text" name="prefix" value="{{ old('prefix', $defaultPrefix) }}"
                                   class="input" required maxlength="10" pattern="[A-Za-z0-9]+">
                            <p class="text-xs text-slate-500 mt-1">Sin espacios ni caracteres especiales</p>
                            @error('prefix') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="label">Estado</label>
                            <label class="flex items-center gap-2 mt-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                                <span class="text-sm">Rango activo</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Número inicial (Desde)</label>
                            <input type="number" name="from_number" value="{{ old('from_number', 1) }}"
                                   class="input" required min="1">
                            @error('from_number') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="label label-required">Número final (Hasta)</label>
                            <input type="number" name="to_number" value="{{ old('to_number', 99999) }}"
                                   class="input" required min="1">
                            @error('to_number') <p class="form-error">{{ $message }}</p> @enderror
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
                        <input type="text" name="resolution_number" value="{{ old('resolution_number') }}"
                               class="input" maxlength="100" placeholder="Ej: 18760000001-2024">
                        @error('resolution_number') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Fecha de resolución</label>
                            <input type="date" name="resolution_date" value="{{ old('resolution_date') }}" class="input">
                            @error('resolution_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="label">Fecha de vencimiento</label>
                            <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" class="input">
                            @error('expiration_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Clave técnica</label>
                        <input type="text" name="technical_key" value="{{ old('technical_key') }}"
                               class="input" maxlength="100">
                        @error('technical_key') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label class="label">Notas</label>
                        <textarea name="notes" rows="3" class="input">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <div class="p-6 space-y-3">
                    <button type="submit" class="btn btn-primary w-full">Crear rango</button>
                    <a href="{{ route('settings.numbering.index') }}" class="btn btn-outline w-full">Cancelar</a>
                </div>
            </x-card>

            <x-card>
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Ayuda</h3>
                    <ul class="text-xs text-slate-600 space-y-2">
                        <li>• El rango debe estar autorizado por la DIAN.</li>
                        <li>• El prefijo identifica la serie del documento.</li>
                        <li>• Los rangos no pueden solaparse entre sí.</li>
                        <li>• Una vez asignado un número, no puede reducir el rango.</li>
                    </ul>
                </div>
            </x-card>
        </div>
    </div>
</form>
@endsection