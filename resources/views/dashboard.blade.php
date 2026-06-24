@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $hour = (int) now()->format('H');
    $greeting = match (true) {
        $hour < 12 => 'Buenos días',
        $hour < 19 => 'Buenas tardes',
        default => 'Buenas noches',
    };
    $userName = auth()->user()->name ?? '';
    $firstName = explode(' ', trim($userName))[0];
@endphp
<div class="space-y-6">
    <div class="page-header">
        <div class="page-header-actions">
            <div>
                <h1 class="page-title">{{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}</h1>
                <p class="page-subtitle">Aquí está un resumen de tu actividad</p>
            </div>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary px-5 py-2.5 text-sm font-semibold rounded-lg shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Factura
            </a>
        </div>
    </div>

    @if(count($numberingAlerts ?? []) > 0)
    <div class="space-y-2 mb-4">
        @foreach($numberingAlerts as $alert)
            <a href="{{ route('settings.numbering.index') }}"
               class="flex items-start gap-3 p-4 rounded-lg border
                {{ $alert['level'] === 'critical' ? 'bg-red-50 border-red-200 hover:bg-red-100' : 'bg-amber-50 border-amber-200 hover:bg-amber-100' }}
                transition">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $alert['level'] === 'critical' ? 'text-red-600' : 'text-amber-600' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <p class="font-semibold {{ $alert['level'] === 'critical' ? 'text-red-800' : 'text-amber-800' }}">
                        {{ $alert['message'] }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>
    @endif

    @if($alerts['overdue'] > 0 || $alerts['rejected'] > 0 || $alerts['old_drafts'] > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @if($alerts['overdue'] > 0)
        <div class="card border-l-4 border-l-red-500 bg-red-50">
            <div class="p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-700">{{ $alerts['overdue'] }}</p>
                    <p class="text-sm text-red-600">Facturas vencidas</p>
                </div>
            </div>
        </div>
        @endif

        @if($alerts['rejected'] > 0)
        <div class="card border-l-4 border-l-orange-500 bg-orange-50">
            <div class="p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-orange-700">{{ $alerts['rejected'] }}</p>
                    <p class="text-sm text-orange-600">Rechazadas (7 días)</p>
                </div>
            </div>
        </div>
        @endif

        @if($alerts['old_drafts'] > 0)
        <div class="card border-l-4 border-l-amber-500 bg-amber-50">
            <div class="p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-700">{{ $alerts['old_drafts'] }}</p>
                    <p class="text-sm text-amber-600">Borradores antiguos</p>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Facturas</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="stat-icon bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Borradores</p>
                    <p class="text-3xl font-bold text-amber-600 mt-2">{{ $stats['draft'] ?? 0 }}</p>
                </div>
                <div class="stat-icon bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Pendientes</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['pending'] ?? 0 }}</p>
                </div>
                <div class="stat-icon bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Enviadas</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['sent'] ?? 0 }}</p>
                </div>
                <div class="stat-icon bg-green-100">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @if(isset($paymentStats))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
        <div class="stat-card border-l-4 border-l-emerald-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Recaudado este mes</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($paymentStats['total_collected'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $paymentStats['count'] ?? 0 }} pago(s)</p>
                </div>
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-l-amber-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Por cobrar</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">${{ number_format($accountsReceivable['totals']['total'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $accountsReceivable['invoice_count'] ?? 0 }} factura(s)</p>
                </div>
                <div class="stat-icon bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-l-red-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Vencido +30 días</p>
                    @php
                        $overdueTotal = ($accountsReceivable['totals']['31-60'] ?? 0)
                                       + ($accountsReceivable['totals']['61-90'] ?? 0)
                                       + ($accountsReceivable['totals']['90+'] ?? 0);
                        $overdueCount = ($accountsReceivable['groups']['31-60'] ?? collect())->count()
                                      + ($accountsReceivable['groups']['61-90'] ?? collect())->count()
                                      + ($accountsReceivable['groups']['90+'] ?? collect())->count();
                    @endphp
                    <p class="text-2xl font-bold text-red-600 mt-1">${{ number_format($overdueTotal, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $overdueCount }} factura(s)</p>
                </div>
                <div class="stat-icon bg-red-100">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        @if(isset($payableStats))
        <div class="stat-card border-l-4 border-l-rose-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Por pagar</p>
                    <p class="text-2xl font-bold text-rose-600 mt-1">${{ number_format($payableStats['totals']['total'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $payableStats['invoice_count'] ?? 0 }} factura(s) a proveedores</p>
                </div>
                <div class="stat-icon bg-rose-100">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(isset($inventoryAlerts) && ($inventoryAlerts['low_stock_count'] > 0 || $inventoryAlerts['out_of_stock_count'] > 0))
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="stat-card border-l-4 border-l-amber-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Stock bajo</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $inventoryAlerts['low_stock_count'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">productos requieren reabastecimiento</p>
                </div>
                <div class="stat-icon bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-l-red-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Agotados</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $inventoryAlerts['out_of_stock_count'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">productos sin stock</p>
                </div>
                <div class="stat-icon bg-red-100">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-l-emerald-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Valor del inventario</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($inventoryAlerts['total_value'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $inventoryAlerts['product_count'] }} productos rastreados</p>
                </div>
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Tendencia de Facturas</h2>
            </div>
            <div class="p-6">
                <div id="invoiceChartContainer" class="relative h-64">
                    <canvas id="invoiceChart"></canvas>
                    @if($charts['invoice_counts'] instanceof \Illuminate\Support\Collection ? $charts['invoice_counts']->sum() == 0 : array_sum((array) $charts['invoice_counts']) == 0)
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-slate-500">Sin datos aún</p>
                            <a href="{{ route('invoices.create') }}" class="text-xs text-blue-600 hover:underline">Crear primera factura</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Ingresos Mensuales</h2>
            </div>
            <div class="p-6">
                <div id="revenueChartContainer" class="relative h-64">
                    <canvas id="revenueChart"></canvas>
                    @if($charts['invoice_amounts'] instanceof \Illuminate\Support\Collection ? $charts['invoice_amounts']->sum() == 0 : array_sum((array) $charts['invoice_amounts']) == 0)
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-slate-500">Sin ingresos aún</p>
                            <p class="text-xs text-slate-400">Los ingresos aparecerán aquí</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Actividad Reciente</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($activity['invoices'] as $invoice)
                    <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center gap-4 p-4 hover:bg-slate-50:bg-slate-700/50 transition-colors">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center @switch($invoice->status)
                            @case('draft') bg-amber-100 text-amber-600 @break
                            @case('pending') bg-blue-100 text-blue-600 @break
                            @case('sent') bg-green-100 text-green-600 @break
                            @case('approved') bg-green-100 text-green-600 @break
                            @case('rejected') bg-red-100 text-red-600 @break
                            @case('cancelled') bg-slate-100 text-slate-600 @break
                            @default bg-slate-100 text-slate-600
                        @endswitch">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $invoice->number ?? 'Borrador' }}</p>
                            <p class="text-xs text-slate-500">{{ $invoice->client?->name ?? 'Sin cliente' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900">${{ number_format($invoice->total, 0) }}</p>
                            <p class="text-xs text-slate-500">{{ $invoice->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                    @empty
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-600 font-medium mb-1">Sin actividad reciente</p>
                        <a href="{{ route('invoices.create') }}" class="text-sm text-blue-600 hover:underline">Crear primera factura</a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Estado de facturas</h2>
                    <a href="{{ route('invoices.index') }}" class="text-xs text-blue-600 hover:underline">Ver todas</a>
                </div>
                <div class="p-6">
                    @if(($charts['status_distribution'] instanceof \Illuminate\Support\Collection ? $charts['status_distribution']->sum() : array_sum((array) ($charts['status_distribution'] ?? []))) > 0)
                    <div class="space-y-3">
                        @php
                            $total = ($charts['status_distribution'] instanceof \Illuminate\Support\Collection) ? $charts['status_distribution']->sum() : array_sum((array) $charts['status_distribution']);
                        @endphp
                        @foreach(['draft' => ['label' => 'Borrador', 'color' => 'bg-amber-400'], 'pending' => ['label' => 'Pendiente', 'color' => 'bg-blue-400'], 'sent' => ['label' => 'Enviada', 'color' => 'bg-green-400'], 'approved' => ['label' => 'Aprobada', 'color' => 'bg-green-600'], 'rejected' => ['label' => 'Rechazada', 'color' => 'bg-red-400'], 'cancelled' => ['label' => 'Cancelada', 'color' => 'bg-slate-400']] as $key => $meta)
                            @if(($charts['status_distribution']->has($key) && $charts['status_distribution'][$key] > 0) || (is_array($charts['status_distribution']) && ($charts['status_distribution'][$key] ?? 0) > 0))
                                @php
                                    $count = $charts['status_distribution']->has($key) ? $charts['status_distribution'][$key] : ($charts['status_distribution'][$key] ?? 0);
                                    $pct = $total > 0 ? round(($count / $total) * 100) : 0;
                                @endphp
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full {{ $meta['color'] }}"></span>
                                            <span class="text-slate-700">{{ $meta['label'] }}</span>
                                        </div>
                                        <span class="font-semibold text-slate-900">{{ $count }} <span class="text-xs text-slate-400 font-normal">({{ $pct }}%)</span></span>
                                    </div>
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $meta['color'] }} rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    @else
                    <div class="py-8 text-center">
                        <p class="text-sm text-slate-500">Sin facturas aún</p>
                        <a href="{{ route('invoices.create') }}" class="text-xs text-blue-600 hover:underline">Crear primera factura</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Acciones Rápidas</h2>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('invoices.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50:bg-slate-700/50 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Nueva Factura</p>
                            <p class="text-xs text-slate-500">Crear documento</p>
                        </div>
                    </a>
                    <a href="{{ route('clients.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50:bg-slate-700/50 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Nuevo Cliente</p>
                            <p class="text-xs text-slate-500">Agregar empresa</p>
                        </div>
                    </a>
                    <a href="{{ route('products.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50:bg-slate-700/50 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Nuevo Producto</p>
                            <p class="text-xs text-slate-500">Crear servicio</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = {!! json_encode($charts['labels']) !!};
    const invoiceCounts = {!! json_encode($charts['invoice_counts']) !!};
    const invoiceAmounts = {!! json_encode($charts['invoice_amounts']) !!};
    const statusData = {!! json_encode($charts['status_distribution'] ?? []) !!};

    const hasInvoiceData = invoiceCounts.some(v => v > 0);
    const hasRevenueData = invoiceAmounts.some(v => v > 0);
    const hasStatusData = Object.values(statusData).some(v => v > 0);

    const textColor = '#64748b';
    const gridColor = '#f1f5f9';

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2.5,
        plugins: {
            legend: {
                display: false
            }
        }
    };

    if (hasInvoiceData) {
        new Chart(document.getElementById('invoiceChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Facturas',
                    data: invoiceCounts,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    if (hasRevenueData) {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos',
                    data: invoiceAmounts,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    const statusColors = {
        draft: '#facc15',
        pending: '#3b82f6',
        sent: '#22c55e',
        approved: '#16a34a',
        rejected: '#ef4444',
        cancelled: '#94a3b8'
    };
});
</script>
@endpush
@endsection
