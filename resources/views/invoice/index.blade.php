@extends('layouts.app')

@section('title', 'Facturas')

@section('content')
<x-page-header title="Facturas" subtitle="Gestiona tus facturas y documentos">
    <x-slot:actions>
        <x-button :href="route('invoices.create')" variant="primary" icon="plus">Nueva Factura</x-button>
    </x-slot:actions>
</x-page-header>

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

<x-card>
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-input name="search" placeholder="Buscar por número..." :value="request('search')" />
            </div>
            <x-select
                name="status"
                :options="['draft' => 'Borrador', 'pending' => 'Pendiente', 'sent' => 'Enviada', 'approved' => 'Aprobada', 'rejected' => 'Rechazada', 'cancelled' => 'Cancelada']"
                placeholder="Todos los estados"
                :value="request('status')"
                class="w-auto"
            />
            <x-select
                name="client_id"
                :options="\App\Models\Client::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->pluck('name', 'id')"
                placeholder="Todos los clientes"
                :value="request('client_id')"
                class="w-auto"
            />
            <button type="submit" class="btn btn-outline">Filtrar</button>
            @if(request()->hasAny(['search', 'status', 'client_id']))
                <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th class="text-right">Total</th>
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
                    <td class="text-slate-600">{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-slate-600">
                        @if($invoice->due_date)
                            {{ $invoice->due_date->format('d/m/Y') }}
                            @if($invoice->isOverdue() && $invoice->hasOutstandingBalance())
                                <span class="ml-1 text-xs text-red-600 font-semibold">{{ $invoice->daysOverdue() }}d</span>
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="font-medium text-slate-900 text-right">${{ number_format($invoice->total, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $statusVariants = [
                                'draft' => 'warning',
                                'pending' => 'info',
                                'sent' => 'success',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'default',
                            ];
                            $statusLabels = [
                                'draft' => 'Borrador',
                                'pending' => 'Pendiente',
                                'sent' => 'Enviada',
                                'approved' => 'Aprobada',
                                'rejected' => 'Rechazada',
                                'cancelled' => 'Cancelada',
                            ];
                        @endphp
                        <x-badge :variant="$statusVariants[$invoice->status] ?? 'default'">
                            {{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}
                        </x-badge>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn-ghost p-2" title="Ver">
                                <x-icon name="eye" class="w-4 h-4" />
                            </a>
                            @can('update', $invoice)
                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-ghost p-2" title="Editar">
                                    <x-icon name="edit" class="w-4 h-4" />
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state
                            title="No hay facturas registradas"
                            description="Comienza creando tu primera factura"
                            :action-href="route('invoices.create')"
                            action-label="Crear la primera"
                        />
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
</x-card>
@endsection