@extends('layouts.app')

@section('title', 'Numeración DIAN')

@section('content')
<x-page-header
    title="Numeración DIAN"
    subtitle="Rangos autorizados para facturación electrónica"
    :back="route('settings.index')">
    <x-slot:actions>
        @can('create', App\Models\NumberingRange::class)
            <a href="{{ route('settings.numbering.create') }}" class="btn btn-primary px-4 py-2 text-sm font-semibold rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo rango
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(count($alerts) > 0)
    <div class="mb-6 space-y-2">
        @foreach($alerts as $alert)
            <div class="flex items-start gap-3 p-4 rounded-lg border
                {{ $alert['level'] === 'critical' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($alert['level'] === 'critical')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @endif
                </svg>
                <div class="flex-1">
                    <p class="font-semibold">{{ $alert['message'] }}</p>
                    <p class="text-xs mt-1">
                        @if($alert['level'] === 'critical')
                            Configure un nuevo rango antes de seguir facturando.
                        @else
                            Revise y planifique un nuevo rango si es necesario.
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>
@endif

<x-card>
    <div class="px-6 py-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo documento</label>
                <select name="document_type" class="input">
                    <option value="">Todos</option>
                    <option value="invoice" @selected(request('document_type') === 'invoice')>Factura</option>
                    <option value="credit_note" @selected(request('document_type') === 'credit_note')>Nota Crédito</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                <select name="is_active" class="input">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('is_active') === '1')>Activos</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactivos</option>
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-6 py-3 text-left">Tipo</th>
                    <th class="px-6 py-3 text-left">Prefijo</th>
                    <th class="px-6 py-3 text-left">Rango</th>
                    <th class="px-6 py-3 text-left">Resolución</th>
                    <th class="px-6 py-3 text-left">Vigencia</th>
                    <th class="px-6 py-3 text-left">Uso</th>
                    <th class="px-6 py-3 text-left">Estado</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($ranges as $range)
                    <tr>
                        <td class="px-6 py-4">
                            {{ \App\Models\NumberingRange::TYPES[$range->document_type] }}
                        </td>
                        <td class="px-6 py-4 font-mono">{{ $range->prefix }}</td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-slate-500">Desde</span> {{ number_format($range->from_number) }}
                            <br>
                            <span class="text-xs text-slate-500">Hasta</span> {{ number_format($range->to_number) }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $range->resolution_number ?? '—' }}<br>
                            <span class="text-xs text-slate-500">{{ $range->resolution_date?->format('Y-m-d') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $range->expiration_date?->format('Y-m-d') ?? '—' }}
                        </td>
                        <td class="px-6 py-4 w-48">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium">{{ $range->usagePercentage() }}%</span>
                                <span class="text-xs text-slate-500">{{ number_format($range->availableCount()) }} restantes</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                @php
                                    $pct = $range->usagePercentage();
                                    $color = $pct >= 100 ? 'bg-red-600' : ($pct >= 90 ? 'bg-amber-500' : 'bg-emerald-500');
                                @endphp
                                <div class="h-1.5 rounded-full {{ $color }}" style="width: {{ min($pct, 100) }}%"></div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($range->is_active)
                                <x-badge variant="success">Activo</x-badge>
                            @else
                                <x-badge variant="default">Inactivo</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('update', $range)
                                <a href="{{ route('settings.numbering.edit', $range) }}" class="text-blue-600 hover:underline text-xs">Editar</a>
                            @endcan
                            @can('delete', $range)
                                <form action="{{ route('settings.numbering.destroy', $range) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este rango?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-xs">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            No hay rangos de numeración configurados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4">
        {{ $ranges->links() }}
    </div>
</x-card>
@endsection