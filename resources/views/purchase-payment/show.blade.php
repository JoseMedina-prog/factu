@extends('layouts.app')

@section('title', 'Pago #' . $purchasePayment->id)

@section('content')
<x-page-header :title="'Pago #' . $purchasePayment->id" :subtitle="$purchasePayment->supplier?->name" :back="route('purchase-payments.index')" />

<div class="max-w-2xl">
    <x-card>
        <dl class="p-6 grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-slate-500 text-xs uppercase">Proveedor</dt>
                <dd class="font-medium">{{ $purchasePayment->supplier?->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase">Factura</dt>
                <dd>
                    @if($purchasePayment->purchaseInvoice)
                        <a href="{{ route('purchases.show', $purchasePayment->purchaseInvoice) }}" class="text-blue-600 hover:underline font-mono">{{ $purchasePayment->purchaseInvoice->number }}</a>
                    @else
                        Anticipo
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase">Fecha</dt>
                <dd class="font-medium">{{ $purchasePayment->payment_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase">Método</dt>
                <dd class="font-medium">{{ $purchasePayment->method_label }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase">Monto</dt>
                <dd class="text-2xl font-bold text-primary-700">${{ number_format($purchasePayment->amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase">Estado</dt>
                <dd>
                    @php $v = ['confirmed' => 'success', 'pending' => 'warning', 'cancelled' => 'default']; @endphp
                    <x-badge :variant="$v[$purchasePayment->status] ?? 'default'">{{ ucfirst($purchasePayment->status) }}</x-badge>
                </dd>
            </div>
            @if($purchasePayment->reference)
            <div class="col-span-2">
                <dt class="text-slate-500 text-xs uppercase">Referencia</dt>
                <dd class="font-mono text-sm">{{ $purchasePayment->reference }}</dd>
            </div>
            @endif
            @if($purchasePayment->notes)
            <div class="col-span-2">
                <dt class="text-slate-500 text-xs uppercase">Notas</dt>
                <dd>{{ $purchasePayment->notes }}</dd>
            </div>
            @endif
            <div class="col-span-2 pt-4 border-t border-slate-100 text-xs text-slate-500">
                Registrado por {{ $purchasePayment->creator->name ?? '—' }} el {{ $purchasePayment->created_at->format('d/m/Y H:i') }}
                @if($purchasePayment->confirmer)
                    · Confirmado por {{ $purchasePayment->confirmer->name }}
                @endif
            </div>
        </dl>
    </x-card>
</div>
@endsection