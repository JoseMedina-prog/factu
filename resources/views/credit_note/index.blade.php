@extends('layouts.app')

@section('title', 'Notas de Crédito')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Notas de Crédito</h1>
            <p class="page-subtitle">Gestiona devoluciones y Notas Crédito</p>
        </div>
        <a href="{{ route('credit-notes.create') }}" class="btn btn-primary px-5 py-2.5 text-sm font-semibold rounded-lg shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Nota de Crédito
        </a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Total</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Borradores</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['draft'] }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Aprobadas</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Canceladas</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['cancelled'] }}</p>
    </div>
</div>

<div class="card">
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="status" class="input w-auto" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Borrador</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Aprobada</option>
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
                @forelse ($creditNotes as $creditNote)
                <tr>
                    <td>
                        <a href="{{ route('credit-notes.show', $creditNote) }}" class="font-medium text-blue-600 hover:underline">
                            {{ $creditNote->number }}
                        </a>
                    </td>
                    <td class="text-slate-700">{{ $creditNote->client->name }}</td>
                    <td class="text-slate-600">{{ $creditNote->issue_date->format('d/m/Y') }}</td>
                    <td class="font-medium text-slate-900">${{ number_format($creditNote->total, 2) }}</td>
                    <td>
                        @switch($creditNote->status)
                            @case('draft')
                                <span class="badge badge-warning">Borrador</span>
                                @break
                            @case('approved')
                                <span class="badge badge-success">Aprobada</span>
                                @break
                            @case('cancelled')
                                <span class="badge badge-danger">Cancelada</span>
                                @break
                        @endswitch
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('credit-notes.show', $creditNote) }}" class="btn btn-ghost p-2" title="Ver">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            @if($creditNote->status === 'draft')
                                <a href="{{ route('credit-notes.edit', $creditNote) }}" class="btn btn-ghost p-2" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            @endif
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
                            <p class="text-slate-600 font-medium">No hay notas de crédito</p>
                            <a href="{{ route('credit-notes.create') }}" class="text-sm text-blue-600 hover:underline mt-1">Crear la primera</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($creditNotes->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $creditNotes->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
