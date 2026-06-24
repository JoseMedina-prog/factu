@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Reporte de Ventas</h1>
            <p class="page-subtitle">Resumen de facturas y notas crédito</p>
        </div>
        <a href="{{ route('reports.invoices.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success px-5 py-2.5 text-sm font-semibold rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exportar Excel
        </a>
    </div>
</div>

<div class="card mb-6">
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Desde</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="input">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Total Facturado</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($totalInvoiced, 0) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Notas Crédito</p>
        <p class="text-2xl font-bold text-red-600 mt-1">-${{ number_format($totalCredits, 0) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Ventas Netas</p>
        <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($netSales, 0) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Top 10 Clientes</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($byClient as $client)
            <div class="flex items-center justify-between p-4">
                <div>
                    <p class="font-medium text-slate-900">{{ $client['name'] }}</p>
                    <p class="text-sm text-slate-500">{{ $client['count'] }} facturas</p>
                </div>
                <p class="font-semibold text-slate-900">${{ number_format($client['total'], 0) }}</p>
            </div>
            @empty
            <div class="p-4 text-center text-slate-500">Sin datos</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Productos Más Vendidos</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($byProduct as $product)
            <div class="flex items-center justify-between p-4">
                <div>
                    <p class="font-medium text-slate-900">{{ Str::limit($product['description'], 40) }}</p>
                    <p class="text-sm text-slate-500">{{ number_format($product['quantity'], 2) }} unidades</p>
                </div>
                <p class="font-semibold text-slate-900">${{ number_format($product['total'], 0) }}</p>
            </div>
            @empty
            <div class="p-4 text-center text-slate-500">Sin datos</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card">
    <div class="px-6 py-5 border-b border-slate-100">
        <h2 class="text-lg font-semibold text-slate-900">Detalle de Facturas</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td class="text-slate-600">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                    <td class="text-slate-900 font-medium">{{ $invoice->number }}</td>
                    <td class="text-slate-600">{{ $invoice->client->name }}</td>
                    <td class="text-right text-slate-600">${{ number_format($invoice->subtotal, 2) }}</td>
                    <td class="text-right text-slate-600">${{ number_format($invoice->tax, 2) }}</td>
                    <td class="text-right font-medium text-slate-900">${{ number_format($invoice->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-slate-500">No hay facturas en este período</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
