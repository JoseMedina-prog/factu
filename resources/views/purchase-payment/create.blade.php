@extends('layouts.app')

@section('title', 'Registrar pago a proveedor')

@section('content')
<x-page-header
    title="Registrar pago"
    :subtitle="$invoice ? 'Pago para factura ' . $invoice->number : 'Pago a proveedor'"
    :back="$invoice ? route('purchases.show', $invoice) : route('purchase-payments.index')"
/>

<form action="{{ route('purchase-payments.store') }}" method="POST" class="max-w-2xl">
    @csrf

    <x-card>
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900">Datos del pago</h3>
        </div>
        <div class="p-6 space-y-4">
            @if($invoice)
                <input type="hidden" name="purchase_invoice_id" value="{{ $invoice->id }}">
                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-xs text-slate-500 uppercase">Factura</p>
                    <p class="font-mono font-medium">{{ $invoice->number }}</p>
                    <p class="text-sm text-slate-600">{{ $invoice->supplier?->name }}</p>
                    <div class="grid grid-cols-3 gap-3 mt-3 text-sm">
                        <div>
                            <p class="text-xs text-slate-500">Total</p>
                            <p class="font-semibold">${{ number_format($invoice->total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Pagado</p>
                            <p class="font-semibold text-emerald-600">${{ number_format($invoice->paid_amount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Saldo</p>
                            <p class="font-semibold text-amber-600">${{ number_format($invoice->balance, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-500">Esta funcionalidad requiere abrir el pago desde una factura de compra específica.</p>
            @endif

            @if($invoice)
                <x-input name="amount" type="number" label="Monto a pagar" required step="0.01" min="0.01" :value="old('amount', $invoice->balance)" />
                <x-input name="payment_date" type="date" label="Fecha del pago" required :value="old('payment_date', now()->toDateString())" />
                <x-select
                    name="method"
                    label="Método de pago"
                    required
                    :options="\App\Models\PurchasePayment::METHODS"
                    :value="old('method', 'transfer')"
                />
                <x-input name="reference" label="Referencia / Comprobante" placeholder="Número de transferencia, cheque, etc." />
                <x-input name="notes" label="Notas" placeholder="Información adicional del pago" />
            @endif
        </div>
    </x-card>

    @if($invoice)
    <div class="mt-6 flex gap-2 justify-end">
        <a href="{{ route('purchases.show', $invoice) }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">Registrar pago</button>
    </div>
    @endif
</form>
@endsection