@extends('layouts.app')

@section('title', 'Pagos a proveedores')

@section('content')
<x-page-header title="Pagos a proveedores" subtitle="Historial de pagos realizados a proveedores" :back="null">
    <x-slot:actions>
        <a href="{{ route('accounts-payable.index') }}" class="btn btn-outline">Ver CxP</a>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card border-l-4 border-l-emerald-500">
        <p class="text-sm text-slate-500">Pagado este mes</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($stats['total_paid'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-400">{{ $stats['count'] ?? 0 }} pago(s)</p>
    </div>
</div>

<x-card>
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Factura</th>
                <th>Método</th>
                <th>Referencia</th>
                <th class="text-right">Monto</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="text-sm">{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('suppliers.show', $payment->supplier) }}" class="font-medium hover:text-primary-600">{{ $payment->supplier?->name }}</a>
                    </td>
                    <td>
                        @if($payment->purchaseInvoice)
                            <a href="{{ route('purchases.show', $payment->purchaseInvoice) }}" class="font-mono text-xs text-blue-600 hover:underline">{{ $payment->purchaseInvoice->number }}</a>
                        @else
                            <span class="text-xs text-slate-400">Anticipo</span>
                        @endif
                    </td>
                    <td>{{ $payment->method_label }}</td>
                    <td class="text-xs">{{ $payment->reference ?? '—' }}</td>
                    <td class="text-right font-semibold">${{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td>
                        @php $v = ['confirmed' => 'success', 'pending' => 'warning', 'cancelled' => 'default']; @endphp
                        <x-badge :variant="$v[$payment->status] ?? 'default'">{{ ucfirst($payment->status) }}</x-badge>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-slate-500 py-12">No hay pagos registrados aún</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($payments->hasPages())
        <div class="p-4 border-t border-slate-100">{{ $payments->withQueryString()->links() }}</div>
    @endif
</x-card>
@endsection