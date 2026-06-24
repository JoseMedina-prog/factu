@extends('layouts.app')

@section('title', 'Facturas de compra')

@section('content')
<x-page-header title="Facturas de compra" subtitle="Registro de facturas recibidas de proveedores" :back="null">
    <x-slot:actions>
        @can('create', App\Models\PurchaseInvoice::class)
            <x-button :href="route('purchases.create')" variant="primary" icon="plus">Nueva compra</x-button>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-slate-500">Total facturas</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-amber-500">
        <p class="text-sm text-slate-500">Pendientes de pago</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['unpaid'] + $stats['partial'] }}</p>
        <p class="text-xs text-slate-400">${{ number_format($stats['outstanding'], 0, ',', '.') }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-emerald-500">
        <p class="text-sm text-slate-500">Pagadas</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['paid'] }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-blue-500">
        <p class="text-sm text-slate-500">Monto total comprado</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($stats['total_amount'], 0, ',', '.') }}</p>
    </div>
</div>

<x-card>
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[180px]">
                <x-input name="search" placeholder="Buscar por número..." :value="request('search')" />
            </div>
            <div class="flex-1 min-w-[180px]">
                <x-input name="from" type="date" :value="request('from')" />
            </div>
            <div class="flex-1 min-w-[180px]">
                <x-input name="to" type="date" :value="request('to')" />
            </div>
            <x-select name="supplier_id" :options="$suppliers->pluck('name', 'id')" placeholder="Todos los proveedores" :value="request('supplier_id')" class="w-auto" />
            <x-select
                name="payment_status"
                :options="['unpaid' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagada', 'overpaid' => 'Sobrepago']"
                placeholder="Estado de pago"
                :value="request('payment_status')"
                class="w-auto"
            />
            <button type="submit" class="btn btn-outline">Filtrar</button>
            @if(request()->hasAny(['search', 'from', 'to', 'supplier_id', 'payment_status']))
                <a href="{{ route('purchases.index') }}" class="btn btn-ghost">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Proveedor</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Saldo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>
                        <a href="{{ route('purchases.show', $purchase) }}" class="font-mono text-xs text-blue-600 hover:underline">{{ $purchase->number }}</a>
                    </td>
                    <td>
                        @if($purchase->supplier)
                            <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="font-medium text-slate-900 hover:text-primary-600">{{ $purchase->supplier->name }}</a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="text-sm">{{ $purchase->issue_date->format('d/m/Y') }}</td>
                    <td class="text-sm">
                        {{ $purchase->due_date?->format('d/m/Y') ?? '—' }}
                        @if($purchase->isOverdue())
                            <span class="ml-1 text-xs text-red-600 font-semibold">{{ $purchase->daysOverdue() }}d</span>
                        @endif
                    </td>
                    <td class="text-right font-medium">${{ number_format($purchase->total, 0, ',', '.') }}</td>
                    <td class="text-right font-semibold {{ $purchase->balance > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        ${{ number_format($purchase->balance, 0, ',', '.') }}
                    </td>
                    <td>
                        @php
                            $variants = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger', 'overpaid' => 'info'];
                            $labels = ['paid' => 'Pagada', 'partial' => 'Parcial', 'unpaid' => 'Pendiente', 'overpaid' => 'Sobrepago'];
                        @endphp
                        <x-badge :variant="$variants[$purchase->payment_status] ?? 'default'">{{ $labels[$purchase->payment_status] ?? $purchase->payment_status }}</x-badge>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state
                            title="Sin facturas de compra"
                            description="Registra la primera factura recibida de un proveedor"
                            :action-href="route('purchases.create')"
                            action-label="Registrar compra"
                        />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($purchases->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $purchases->withQueryString()->links() }}
    </div>
    @endif
</x-card>
@endsection