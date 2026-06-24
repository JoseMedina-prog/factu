@extends('layouts.app')

@section('title', 'Historial: ' . $product->name)

@section('content')
<x-page-header
    :title="__('Historial: ') . $product->name"
    :subtitle="__('Stock actual: ') . number_format($product->stock, 2) . ' ' . $product->unit_of_measure_label . ' | Mínimo: ' . number_format($product->min_stock, 2)"
    :back="route('inventory.index')" />

<x-card>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-6 py-3 text-left">Fecha</th>
                    <th class="px-6 py-3 text-left">Tipo</th>
                    <th class="px-6 py-3 text-right">Cantidad</th>
                    <th class="px-6 py-3 text-right">Stock antes</th>
                    <th class="px-6 py-3 text-right">Stock después</th>
                    <th class="px-6 py-3 text-right">Costo</th>
                    <th class="px-6 py-3 text-left">Motivo</th>
                    <th class="px-6 py-3 text-left">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($movements as $movement)
                    <tr>
                        <td class="px-6 py-4">{{ $movement->movement_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <x-badge :variant="$movement->type === 'entry' ? 'success' : 'danger'">
                                {{ $movement->type_label }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold
                            {{ $movement->type === 'entry' ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $movement->type === 'entry' ? '+' : '-' }}{{ number_format($movement->quantity, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-500">{{ number_format($movement->stock_before, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold">{{ number_format($movement->stock_after, 2) }}</td>
                        <td class="px-6 py-4 text-right">${{ number_format($movement->total_cost, 2) }}</td>
                        <td class="px-6 py-4 text-xs">{{ $movement->reason ?? '—' }}</td>
                        <td class="px-6 py-4 text-xs">{{ $movement->user->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            Sin movimientos para este producto.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4">
        {{ $movements->links() }}
    </div>
</x-card>
@endsection