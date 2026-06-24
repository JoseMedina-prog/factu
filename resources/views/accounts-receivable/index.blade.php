@extends('layouts.app')

@section('title', 'Cuentas por cobrar')

@section('content')
<x-page-header title="Cuentas por cobrar" subtitle="Saldo que tus clientes te deben, agrupado por antigüedad" :back="route('dashboard')">
    <x-slot:actions>
        <a href="{{ route('accounts-receivable.export', ['as_of' => $asOf]) }}" class="btn btn-outline">
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
        <div class="flex-1 text-right text-sm text-slate-500">
            <strong class="text-slate-900">{{ $invoice_count ?? 0 }}</strong> factura(s) ·
            <strong class="text-slate-900">{{ $client_count ?? 0 }}</strong> cliente(s) con saldo
        </div>
    </form>
</div>

@php
    $buckets = [
        'current' => ['label' => 'Por vencer', 'color' => 'emerald', 'icon' => 'check'],
        '0-30' => ['label' => '0-30 días', 'color' => 'amber', 'icon' => 'clock'],
        '31-60' => ['label' => '31-60 días', 'color' => 'orange', 'icon' => 'warning'],
        '61-90' => ['label' => '61-90 días', 'color' => 'red', 'icon' => 'warning'],
        '90+' => ['label' => 'Más de 90 días', 'color' => 'red', 'icon' => 'alert'],
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
            <h3 class="font-semibold text-slate-900">Facturas pendientes de cobro</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Factura</th>
                        <th class="px-6 py-3 text-left">Cliente</th>
                        <th class="px-6 py-3 text-left">Vencimiento</th>
                        <th class="px-6 py-3 text-right">Días vencidos</th>
                        <th class="px-6 py-3 text-right">Saldo</th>
                        <th class="px-6 py-3 text-left">Cubo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($groups as $bucket => $invoices)
                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 font-mono text-xs">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-blue-600 hover:underline">{{ $invoice->number }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium">{{ $invoice->client->name ?? '—' }}</p>
                                    @if($invoice->client?->phone)
                                        <p class="text-xs text-slate-500">{{ $invoice->client->phone }}</p>
                                    @endif
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
                                <td class="px-6 py-4 text-right">
                                    @if($invoice->hasOutstandingBalance() && auth()->user()->can('create', App\Models\Payment::class))
                                        <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="text-xs text-blue-600 hover:underline">Registrar pago</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No hay cuentas pendientes. ¡Todo al día!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(($totals['total'] ?? 0) > 0)
                    <tfoot class="bg-slate-50 font-bold">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right">Total por cobrar</td>
                            <td class="px-6 py-3 text-right text-base">${{ number_format($totals['total'], 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-card>

    <x-card>
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900">Por cliente</h3>
            <p class="text-xs text-slate-500 mt-0.5">Top deudores ordenado por saldo</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($by_client ?? [] as $item)
                <a href="{{ route('reports.client-statement', $item['client']) }}" class="block px-6 py-3 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ $item['client']->name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-slate-500">{{ $item['count'] }} factura(s)</span>
                                @if(($item['overdue'] ?? 0) > 0)
                                    <span class="text-xs text-red-600 font-medium">· ${{ number_format($item['overdue'], 0, ',', '.') }} vencido</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right ml-3">
                            <p class="font-semibold text-amber-600">${{ number_format($item['total'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-sm text-slate-500">
                    Sin clientes con saldo pendiente
                </div>
            @endforelse
        </div>
    </x-card>
</div>

@if(($totals['total'] ?? 0) > 0)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
    <h4 class="font-semibold text-amber-900 mb-2 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Cómo leer este reporte
    </h4>
    <ul class="text-sm text-amber-800 space-y-1">
        <li>• <strong>Por vencer:</strong> facturas cuya fecha de vencimiento aún no llega. Se espera pago pronto.</li>
        <li>• <strong>0-30 / 31-60 / 61-90:</strong> días transcurridos desde el vencimiento. A mayor antigüedad, mayor riesgo de impago.</li>
        <li>• <strong>Más de 90 días:</strong> considera iniciar gestión de cobro formal o nota crédito por prescripción.</li>
        <li>• <strong>Por cliente:</strong> prioriza la gestión sobre los de mayor saldo y mayor mora.</li>
    </ul>
</div>
@endif
@endsection