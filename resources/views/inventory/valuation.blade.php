@extends('layouts.app')

@section('title', 'Valorización de inventario')

@section('content')
<x-page-header title="Valorización de inventario" subtitle="Valor del stock actual" :back="route('inventory.index')" />

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card border-l-4 border-l-blue-500">
        <p class="text-sm font-medium text-slate-500">Productos rastreados</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $product_count }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-emerald-500">
        <p class="text-sm font-medium text-slate-500">Unidades totales</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($total_units, 2) }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-amber-500">
        <p class="text-sm font-medium text-slate-500">Stock bajo</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $low_stock_count }}</p>
    </div>
    <div class="stat-card border-l-4 border-l-red-500">
        <p class="text-sm font-medium text-slate-500">Agotados</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $out_of_stock_count }}</p>
    </div>
</div>

<x-card>
    <div class="px-6 py-5 border-b border-slate-100">
        <h2 class="text-lg font-semibold text-slate-900">Valor total del inventario</h2>
    </div>
    <div class="p-6">
        <p class="text-4xl font-bold text-slate-900">
            ${{ number_format($total_value, 2, ',', '.') }}
        </p>
        <p class="text-sm text-slate-500 mt-2">Suma del costo × stock de cada producto rastreado</p>
    </div>
</x-card>

@if($low_stock_products->count() > 0 || $out_of_stock_products->count() > 0)
    <x-card class="mt-6">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Alertas de inventario</h2>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th class="text-right">Stock actual</th>
                    <th class="text-right">Stock mínimo</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($out_of_stock_products as $product)
                    <tr class="bg-red-50/30">
                        <td class="font-medium">{{ $product->name }}</td>
                        <td class="font-mono text-xs">{{ $product->sku ?? '—' }}</td>
                        <td class="text-right font-semibold text-red-600">{{ number_format($product->stock, 2) }}</td>
                        <td class="text-right">{{ number_format($product->min_stock, 2) }}</td>
                        <td><x-badge variant="danger">Agotado</x-badge></td>
                        <td class="text-right">
                            <a href="{{ route('inventory.product-history', $product) }}" class="text-blue-600 hover:underline text-xs">Ver historial</a>
                        </td>
                    </tr>
                @endforeach
                @foreach($low_stock_products as $product)
                    @if(!$product->isOutOfStock())
                        <tr class="bg-amber-50/30">
                            <td class="font-medium">{{ $product->name }}</td>
                            <td class="font-mono text-xs">{{ $product->sku ?? '—' }}</td>
                            <td class="text-right font-semibold text-amber-600">{{ number_format($product->stock, 2) }}</td>
                            <td class="text-right">{{ number_format($product->min_stock, 2) }}</td>
                            <td><x-badge variant="warning">Stock bajo</x-badge></td>
                            <td class="text-right">
                                <a href="{{ route('inventory.product-history', $product) }}" class="text-blue-600 hover:underline text-xs">Ver historial</a>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </x-card>
@endif

<x-card class="mt-6">
    <div class="px-6 py-5 border-b border-slate-100">
        <h2 class="text-lg font-semibold text-slate-900">Inventario completo</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-6 py-3 text-left">Producto</th>
                    <th class="px-6 py-3 text-left">SKU</th>
                    <th class="px-6 py-3 text-right">Stock</th>
                    <th class="px-6 py-3 text-right">Costo unit.</th>
                    <th class="px-6 py-3 text-right">Valor total</th>
                    <th class="px-6 py-3 text-right">Precio venta</th>
                    <th class="px-6 py-3 text-right">Margen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @foreach($all_products as $product)
                    @php
                        $stockValue = (float) $product->stock * (float) $product->cost;
                        $sellingValue = (float) $product->stock * (float) $product->price;
                        $margin = $product->cost > 0 ? (($product->price - $product->cost) / $product->cost) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="px-6 py-4">{{ $product->name }}</td>
                        <td class="px-6 py-4 font-mono text-xs">{{ $product->sku ?? '—' }}</td>
                        <td class="px-6 py-4 text-right font-semibold">{{ number_format($product->stock, 2) }} {{ $product->unit_of_measure_label }}</td>
                        <td class="px-6 py-4 text-right">${{ number_format($product->cost, 2) }}</td>
                        <td class="px-6 py-4 text-right font-semibold">${{ number_format($stockValue, 2) }}</td>
                        <td class="px-6 py-4 text-right">${{ number_format($sellingValue, 2) }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="{{ $margin > 30 ? 'text-emerald-600' : ($margin > 0 ? 'text-amber-600' : 'text-red-600') }} font-semibold">
                                {{ number_format($margin, 1) }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 font-bold">
                <tr>
                    <td colspan="4" class="px-6 py-3 text-right">Total inventario:</td>
                    <td class="px-6 py-3 text-right">${{ number_format($total_value, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-card>
@endsection