@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
<div class="max-w-7xl mx-auto">
    <x-page-header
        :title="$supplier->name"
        :subtitle="$supplier->documentDisplay()"
        :back="route('suppliers.index')"
    >
        <x-slot:actions>
            @can('update', $supplier)
                <x-button :href="route('suppliers.edit', $supplier)" variant="outline" icon="edit">Editar</x-button>
            @endcan
            @can('create', App\Models\PurchaseInvoice::class)
                <x-button :href="route('purchases.create', ['supplier_id' => $supplier->id])" variant="primary" icon="plus">Nueva factura</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card border-l-4 border-l-blue-500">
            <p class="text-sm text-slate-500">Facturas de compra</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_invoices'] }}</p>
        </div>
        <div class="stat-card border-l-4 border-l-emerald-500">
            <p class="text-sm text-slate-500">Total comprado</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($stats['total_purchased'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card border-l-4 border-l-green-500">
            <p class="text-sm text-slate-500">Total pagado</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($stats['total_paid'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card border-l-4 border-l-amber-500">
            <p class="text-sm text-slate-500">Saldo pendiente</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">${{ number_format($stats['outstanding'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="lg:col-span-1">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900">Información</h3>
            </div>
            <dl class="p-6 space-y-3 text-sm">
                @if($supplier->contact_name)
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Contacto</dt>
                        <dd class="font-medium">{{ $supplier->contact_name }}</dd>
                    </div>
                @endif
                @if($supplier->email)
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Email</dt>
                        <dd class="font-medium">{{ $supplier->email }}</dd>
                    </div>
                @endif
                @if($supplier->phone)
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Teléfono</dt>
                        <dd class="font-medium">{{ $supplier->phone }}</dd>
                    </div>
                @endif
                @if($supplier->address)
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Dirección</dt>
                        <dd class="font-medium">{{ $supplier->address }}@if($supplier->city), {{ $supplier->city }}@endif</dd>
                    </div>
                @endif
                @if($supplier->bank_name)
                    <div class="pt-3 border-t border-slate-100">
                        <dt class="text-slate-500 text-xs uppercase">Banco</dt>
                        <dd class="font-medium">{{ $supplier->bank_name }}</dd>
                    </div>
                @endif
                @if($supplier->bank_account)
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Cuenta</dt>
                        <dd class="font-mono text-xs">{{ $supplier->bank_account }} @if($supplier->bank_account_type)({{ $supplier->bank_account_type === 'savings' ? 'Ahorros' : 'Corriente' }})@endif</dd>
                    </div>
                @endif
                @if($supplier->notes)
                    <div class="pt-3 border-t border-slate-100">
                        <dt class="text-slate-500 text-xs uppercase">Notas</dt>
                        <dd class="text-slate-700">{{ $supplier->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card class="lg:col-span-2">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900">Facturas de compra recientes</h3>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Emisión</th>
                        <th>Vencimiento</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                        <th>Estado pago</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplier->purchaseInvoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('purchases.show', $inv) }}" class="font-mono text-xs text-blue-600 hover:underline">
                                    {{ $inv->number }}
                                </a>
                            </td>
                            <td class="text-sm">{{ $inv->issue_date->format('d/m/Y') }}</td>
                            <td class="text-sm">{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-right text-sm">${{ number_format($inv->total, 0, ',', '.') }}</td>
                            <td class="text-right text-sm font-semibold {{ $inv->balance > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                ${{ number_format($inv->balance, 0, ',', '.') }}
                            </td>
                            <td>
                                @php
                                    $variants = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger', 'overpaid' => 'info'];
                                    $labels = ['paid' => 'Pagada', 'partial' => 'Parcial', 'unpaid' => 'Pendiente', 'overpaid' => 'Sobrepago'];
                                @endphp
                                <x-badge :variant="$variants[$inv->payment_status] ?? 'default'">{{ $labels[$inv->payment_status] ?? $inv->payment_status }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-slate-500 py-8">Sin facturas de compra aún</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</div>
@endsection