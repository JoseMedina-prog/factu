@extends('layouts.app')

@section('title', 'Detalle de pago')

@section('content')
<x-page-header
    title="Detalle de pago"
    :back="route('payments.index')" />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-card>
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Información del pago</h2>
            </div>
            <div class="p-6 space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600">Monto</span>
                    <span class="text-2xl font-bold text-emerald-600">${{ number_format($payment->amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-3">
                    <span class="text-sm text-slate-600">Fecha</span>
                    <span class="font-medium">{{ $payment->payment_date->format('Y-m-d') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600">Método</span>
                    <span class="font-medium">{{ $payment->method_label }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600">Referencia</span>
                    <span class="font-medium">{{ $payment->reference ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600">Cliente</span>
                    <span class="font-medium">{{ $payment->client->name ?? '—' }}</span>
                </div>
                @if($payment->invoice)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600">Factura</span>
                        <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-blue-600 hover:underline font-mono">
                            {{ $payment->invoice->number }}
                        </a>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600">Registrado por</span>
                    <span class="font-medium">{{ $payment->creator->name ?? '—' }}</span>
                </div>
                @if($payment->confirmer)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600">Confirmado por</span>
                        <span class="font-medium">{{ $payment->confirmer->name }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600">Estado</span>
                    @php
                        $variants = [
                            'confirmed' => 'success',
                            'pending' => 'warning',
                            'cancelled' => 'default',
                            'rejected' => 'danger',
                        ];
                    @endphp
                    <x-badge :variant="$variants[$payment->status] ?? 'default'">
                        {{ ucfirst($payment->status) }}
                    </x-badge>
                </div>
                @if($payment->notes)
                    <div class="border-t border-slate-200 pt-3">
                        <p class="text-sm text-slate-600 mb-1">Notas</p>
                        <p class="text-sm whitespace-pre-line">{{ $payment->notes }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        @if($payment->isPending() && auth()->user()->can('confirm', $payment))
            <x-card>
                <div class="p-6">
                    <form action="{{ route('payments.confirm', $payment) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">Confirmar pago</button>
                    </form>
                </div>
            </x-card>
        @endif

        @if(!$payment->isCancelled() && auth()->user()->can('cancel', $payment))
            <x-card>
                <div class="p-6">
                    <form action="{{ route('payments.cancel', $payment) }}" method="POST"
                          onsubmit="return confirm('¿Cancelar este pago?')">
                        @csrf
                        <textarea name="reason" rows="2" class="input mb-2" placeholder="Motivo (opcional)"></textarea>
                        <button type="submit" class="btn btn-outline w-full text-red-600 border-red-200 hover:bg-red-50">
                            Cancelar pago
                        </button>
                    </form>
                </div>
            </x-card>
        @endif

        @can('delete', $payment)
            <x-card>
                <div class="p-6">
                    <form action="{{ route('payments.destroy', $payment) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar este pago de forma permanente?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline w-full text-red-600 border-red-200 hover:bg-red-50">
                            Eliminar pago
                        </button>
                    </form>
                </div>
            </x-card>
        @endcan
    </div>
</div>
@endsection