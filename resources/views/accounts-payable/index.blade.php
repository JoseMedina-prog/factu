@extends('layouts.app')

@section('title', 'Cuentas por pagar')

@section('content')
<x-page-header title="Cuentas por pagar" subtitle="Lo que debes a tus proveedores agrupado por antigüedad" :back="null">
    <x-slot:actions>
        <a href="{{ route('accounts-payable.export', ['as_of' => $asOf]) }}" class="btn btn-outline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exportar CSV
        </a>
    </x-slot:actions>
</x-page-header>

<div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Corte al</label>
            <input type="date" name="as_of" value="{{ $asOf }}" class="input">
        </div>
        <button type="submit" class="btn btn-outline">Actualizar</button>
    </form>
</div>

@php
    $buckets = [
        'current' => ['label' => 'Por vencer', 'color' => 'emerald'],
        '0-30' => ['label' => '0-30 días', 'color' => 'amber'],
        '31-60' => ['label' => '31-60 días', 'color' => 'orange'],
        '61-90' => ['label' => '61-90 días', 'color' => 'red'],
        '90+' => ['label' => 'Más de 90 días', 'color' => 'red'],
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    @foreach($buckets as $key => $meta)
        <div class="stat-card border-l-4
            @switch($meta['color'])
                @case('emerald') border-l-emerald-500 @break
                @case('amber') border-l-amber-500 @break
                @case('orange') border-l-orange-500 @break
                @case('red') border-l-red-500 @break
            @endswitch">
            <p class="text-xs font-medium text-slate-500">{{ $meta['label'] }}</p>
            <p class="text-xl font-bold text-slate-900 mt-1">
                ${{ number_format($totals[$key] ?? 0, 0, ',', '.') }}
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ ($groups[$key] ?? collect())->count() }} factura(s)
            </p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <x-card class="lg:col-span-2">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900">Facturas pendientes de pago</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Factura</th>
                        <th class="px-6 py-3 text-left">Proveedor</th>
                        <th class="px-6 py-3 text-left">Vencimiento</th>
                        <th class="px-6 py-3 text-right">Días vencidos</th>
                        <th class="px-6 py-3 text-right">Saldo</th>
                        <th class="px-6 py-3 text-left">Cubo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($groups as $bucket => $invoices)
                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 font-mono text-xs">
                                    <a href="{{ route('purchases.show', $invoice) }}" class="text-blue-600 hover:underline">{{ $invoice->number }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('suppliers.show', $invoice->supplier) }}" class="hover:text-primary-600">{{ $invoice->supplier?->name ?? '—' }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    @if($invoice->due_date)
                                        {{ $invoice->due_date->format('d/m/Y') }}
                                    @else
                                        <span class="text-slate-400 text-xs">Sin vencimiento</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($invoice->daysOverdue() > 0)
                                        <span class="font-semibold text-red-600">{{ $invoice->daysOverdue() }}</span>
                                    @else
                                        <span class="text-slate-400">{{ $invoice->due_date ? '0' : '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold">${{ number_format($invoice->balance, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $cubeColors = [
                                            'current' => 'emerald', '0-30' => 'amber', '31-60' => 'orange', '61-90' => 'red', '90+' => 'red',
                                        ];
                                    @endphp
                                    <x-badge :variant="$cubeColors[$bucket] ?? 'default'">{{ $buckets[$bucket]['label'] ?? $bucket }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No tienes cuentas por pagar. ¡Estás al día con tus proveedores!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(($totals['total'] ?? 0) > 0)
                    <tfoot class="bg-slate-50 font-bold">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right">Total por pagar</td>
                            <td class="px-6 py-3 text-right text-base">${{ number_format($totals['total'], 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-card>

    <x-card>
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900">Por proveedor</h3>
            <p class="text-xs text-slate-500 mt-0.5">Top proveedores con saldo pendiente</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($by_supplier as $item)
                <a href="{{ route('suppliers.show', $item['supplier']) }}" class="block px-6 py-3 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ $item['supplier']->name }}</p>
                            <p class="text-xs text-slate-500">{{ $item['count'] }} factura(s)</p>
                        </div>
                        <div class="text-right ml-3">
                            <p class="font-semibold text-amber-600">${{ number_format($item['total'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-sm text-slate-500">
                    Sin proveedores con saldo pendiente
                </div>
            @endforelse
        </div>
    </x-card>
</div>
@endsection