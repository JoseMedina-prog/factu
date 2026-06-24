@extends('layouts.app')

@section('title', 'Registrar pago')

@section('content')
<x-page-header
    title="Registrar pago"
    subtitle="Aplica el pago a una factura existente"
    :back="route('payments.index')" />

<form action="{{ route('payments.store') }}" method="POST" id="paymentForm">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($invoice)
                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                <x-card>
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h2 class="text-lg font-semibold text-slate-900">Factura</h2>
                    </div>
                    <div class="p-6 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600">Número</span>
                            <span class="font-mono">{{ $invoice->number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600">Cliente</span>
                            <span>{{ $invoice->client->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600">Emisión</span>
                            <span>{{ $invoice->issue_date->format('Y-m-d') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 mt-2">
                            <span class="text-sm font-semibold text-slate-900">Total factura</span>
                            <span class="font-semibold">${{ number_format($invoice->total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-600">Ya pagado</span>
                            <span class="text-emerald-600 font-medium">${{ number_format($invoice->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 mt-2">
                            <span class="text-base font-bold text-slate-900">Saldo pendiente</span>
                            <span class="text-base font-bold text-amber-600">${{ number_format($invoice->balance, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </x-card>
            @else
                <x-card>
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h2 class="text-lg font-semibold text-slate-900">Factura</h2>
                    </div>
                    <div class="p-6">
                        <div class="form-group">
                            <label class="label label-required">Número de factura</label>
                            <input type="number" name="invoice_id" value="{{ old('invoice_id') }}"
                                   class="input" required min="1">
                            @error('invoice_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </x-card>
            @endif

            <x-card>
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Datos del pago</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Monto</label>
                            @if($invoice)
                                <input type="number" name="amount" value="{{ old('amount', $invoice->balance) }}"
                                       step="0.01" min="0.01" max="{{ $invoice->balance }}" class="input" required>
                            @else
                                <input type="number" name="amount" value="{{ old('amount') }}"
                                       step="0.01" min="0.01" class="input" required>
                            @endif
                            @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="label label-required">Fecha</label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
                                   max="{{ now()->toDateString() }}" class="input" required>
                            @error('payment_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Método de pago</label>
                            <select name="method" class="input" required>
                                @foreach(\App\Models\Payment::METHODS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('method') === $key || $key === 'transfer')>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('method') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="label">Referencia</label>
                            <input type="text" name="reference" value="{{ old('reference') }}"
                                   class="input" maxlength="100" placeholder="Núm. transacción, cheque, etc.">
                            @error('reference') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Notas</label>
                        <textarea name="notes" rows="3" class="input">{{ old('notes') }}</textarea>
                        @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <div class="p-6 space-y-3">
                    <button type="submit" class="btn btn-primary w-full">
                        Registrar pago
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline w-full">Cancelar</a>
                </div>
            </x-card>

            <x-card>
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Información</h3>
                    <ul class="text-xs text-slate-600 space-y-2">
                        <li>• El pago se aplica a la factura seleccionada.</li>
                        <li>• No se permiten pagos superiores al saldo pendiente.</li>
                        <li>• La fecha no puede ser futura.</li>
                        <li>• La factura se marcará automáticamente como pagada/parcial.</li>
                    </ul>
                </div>
            </x-card>
        </div>
    </div>
</form>
@endsection