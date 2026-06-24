@extends('layouts.app')

@section('title', 'Inventario - Movimientos')

@section('content')
<x-page-header title="Inventario" subtitle="Movimientos de stock" :back="route('dashboard')">
    <x-slot:actions>
        <a href="{{ route('inventory.valuation') }}" class="btn btn-outline px-4 py-2 text-sm font-semibold rounded-lg">
            Valorización
        </a>
        <button onclick="document.getElementById('entryModal').classList.remove('hidden')"
                class="btn btn-primary px-4 py-2 text-sm font-semibold rounded-lg">
            + Nueva entrada
        </button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card border-l-4 border-l-emerald-500">
        <p class="text-sm font-medium text-slate-500">Entradas</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['entries_count'] ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-0.5">${{ number_format($stats['entries_value'] ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-red-500">
        <p class="text-sm font-medium text-slate-500">Salidas</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['exits_count'] ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-0.5">${{ number_format($stats['exits_value'] ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-blue-500">
        <p class="text-sm font-medium text-slate-500">Período</p>
        <p class="text-sm font-bold text-slate-900 mt-1">
            {{ \Carbon\Carbon::parse($stats['period']['start'])->format('d/m/Y') }}
            -
            {{ \Carbon\Carbon::parse($stats['period']['end'])->format('d/m/Y') }}
        </p>
    </div>
    <div class="stat-card border-l-4 border-l-purple-500">
        <p class="text-sm font-medium text-slate-500">Total movimientos</p>
        <p class="text-2xl font-bold text-purple-600 mt-1">{{ ($stats['entries_count'] ?? 0) + ($stats['exits_count'] ?? 0) }}</p>
    </div>
</div>

<x-card>
    <div class="px-6 py-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Producto</label>
                <select name="product_id" class="input">
                    <option value="">Todos</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                <select name="type" class="input">
                    <option value="">Todos</option>
                    @foreach(\App\Models\InventoryMovement::TYPES as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="input">
            </div>
            <button type="submit" class="btn btn-outline">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-6 py-3 text-left">Fecha</th>
                    <th class="px-6 py-3 text-left">Producto</th>
                    <th class="px-6 py-3 text-left">Tipo</th>
                    <th class="px-6 py-3 text-right">Cantidad</th>
                    <th class="px-6 py-3 text-right">Stock antes</th>
                    <th class="px-6 py-3 text-right">Stock después</th>
                    <th class="px-6 py-3 text-left">Motivo</th>
                    <th class="px-6 py-3 text-left">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($movements as $movement)
                    <tr>
                        <td class="px-6 py-4">{{ $movement->movement_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('inventory.product-history', $movement->product) }}" class="text-blue-600 hover:underline">
                                {{ $movement->product->name ?? '—' }}
                            </a>
                            @if($movement->product?->sku)
                                <span class="text-xs text-slate-500 ml-1 font-mono">{{ $movement->product->sku }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $typeColors = [
                                    'entry' => 'success',
                                    'exit' => 'danger',
                                    'adjustment' => 'warning',
                                    'transfer' => 'info',
                                    'loss' => 'danger',
                                ];
                            @endphp
                            <x-badge :variant="$typeColors[$movement->type] ?? 'default'">{{ $movement->type_label }}</x-badge>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold
                            {{ in_array($movement->type, ['entry']) ? 'text-emerald-600' : '' }}
                            {{ in_array($movement->type, ['exit', 'loss']) ? 'text-red-600' : '' }}">
                            {{ in_array($movement->type, ['entry']) ? '+' : (in_array($movement->type, ['exit', 'loss']) ? '-' : '') }}{{ number_format($movement->quantity, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-500">{{ number_format($movement->stock_before, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold">{{ number_format($movement->stock_after, 2) }}</td>
                        <td class="px-6 py-4 text-xs">{{ $movement->reason ?? '—' }}</td>
                        <td class="px-6 py-4 text-xs">{{ $movement->user->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            No hay movimientos de inventario en el período seleccionado.
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

<div id="entryModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Registrar entrada de inventario</h3>
        <form action="{{ route('inventory.entry') }}" method="POST">
            @csrf
            <div class="space-y-3">
                <div class="form-group">
                    <label class="label label-required">Producto</label>
                    <select name="product_id" class="input" required>
                        <option value="">Seleccionar</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="label label-required">Cantidad</label>
                        <input type="number" name="quantity" step="0.01" min="0.01" class="input" required>
                    </div>
                    <div class="form-group">
                        <label class="label">Costo unitario</label>
                        <input type="number" name="unit_cost" step="0.01" min="0" class="input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Referencia</label>
                    <input type="text" name="reference" class="input" maxlength="100" placeholder="Núm. factura proveedor">
                </div>
                <div class="form-group">
                    <label class="label">Notas</label>
                    <textarea name="notes" rows="2" class="input"></textarea>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn-primary flex-1">Registrar entrada</button>
                <button type="button" onclick="document.getElementById('entryModal').classList.add('hidden')" class="btn btn-outline flex-1">Cancelar</button>
            </div>
        </form>
    </div>
</div>
@endsection