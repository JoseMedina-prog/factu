@extends('layouts.app')

@section('title', 'Facturas')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Facturas</h1>
            <p class="page-subtitle">Gestiona tus facturas y documentos</p>
        </div>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary px-5 py-2.5 text-sm font-semibold rounded-lg shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Factura
        </a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Total Facturas</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">
            {{ number_format($stats['total'] ?? 0, 0) }}
        </p>
        <p class="text-xs text-slate-400 mt-0.5">
            {{ ($stats['total'] ?? 0) === 1 ? 'factura registrada' : 'facturas registradas' }}
        </p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Borradores</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($stats['draft'] ?? 0, 0) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Pendientes</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['pending'] ?? 0, 0) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Enviadas</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['sent'] ?? 0, 0) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Monto Total</p>
        <p class="text-2xl font-bold text-primary-600 mt-1">${{ number_format($stats['total_amount'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-400 mt-0.5">facturas activas</p>
    </div>
</div>

<div class="card">
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="Buscar por número..."
                       value="{{ request('search') }}"
                       class="input">
            </div>
            <select name="status" class="input w-auto" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Borrador</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Enviada</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Aprobada</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rechazada</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                <tr>
                    <td>
                        <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-primary-600 hover:underline">
                            {{ $invoice->number ?? 'Borrador' }}
                        </a>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-slate-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-slate-600">{{ substr($invoice->client->name, 0, 1) }}</span>
                            </div>
                            <span class="text-slate-700">{{ $invoice->client->name }}</span>
                        </div>
                    </td>
                    <td class="text-slate-600">{{ $invoice->issue_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td class="font-medium text-slate-900">${{ number_format($invoice->total, 2) }}</td>
                    <td>
                        @switch($invoice->status)
                            @case('draft')
                                <span class="badge-warning">Borrador</span>
                                @break
                            @case('pending')
                                <span class="badge-info">Pendiente</span>
                                @break
                            @case('sent')
                                <span class="badge-success">Enviada</span>
                                @break
                            @case('approved')
                                <span class="badge-success">Aprobada</span>
                                @break
                            @case('rejected')
                                <span class="badge-danger">Rechazada</span>
                                @break
                            @case('cancelled')
                                <span class="badge-neutral">Cancelada</span>
                                @break
                            @default
                                <span class="badge-neutral">{{ ucfirst($invoice->status) }}</span>
                        @endswitch
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn-ghost p-2" title="Ver">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            @can('update', $invoice)
                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-ghost p-2" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <p class="text-slate-600 font-medium">No hay facturas registradas</p>
                            <a href="{{ route('invoices.create') }}" class="text-sm text-primary-600 hover:underline mt-1">Crear la primera</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $invoices->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
