@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Productos y Servicios</h1>
            <p class="page-subtitle">Gestiona tu catálogo de productos</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary px-5 py-2.5 text-sm font-semibold rounded-lg shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Producto
        </a>
    </div>
</div>

<div class="card">
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="Buscar..."
                       value="{{ request('search') }}"
                       class="input">
            </div>
            <select name="type" class="input w-auto" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>Productos</option>
                <option value="service" {{ request('type') == 'service' ? 'selected' : '' }}>Servicios</option>
            </select>
            <select name="is_active" class="input w-auto" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Activos</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactivos</option>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Precio</th>
                    <th>Impuesto</th>
                    <th class="text-right">Stock</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $product->type === 'product' ? 'bg-blue-100' : 'bg-sky-100' }}">
                                @if($product->type === 'product')
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ Str::limit($product->description, 40) }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($product->type === 'product')
                            <span class="badge-info">Producto</span>
                        @else
                            <span class="badge-info">Servicio</span>
                        @endif
                    </td>
                    <td class="font-medium text-slate-900">${{ number_format($product->price, 2) }}</td>
                    <td class="text-slate-600">{{ $product->tax }}%</td>
                    <td class="text-right">
                        @if($product->track_inventory)
                            @if($product->isOutOfStock())
                                <span class="font-semibold text-red-600">{{ number_format($product->stock, 2) }}</span>
                                <span class="badge-danger ml-1">Agotado</span>
                            @elseif($product->isLowStock())
                                <span class="font-semibold text-amber-600">{{ number_format($product->stock, 2) }}</span>
                                <span class="badge-warning ml-1">Bajo</span>
                            @else
                                <span class="font-medium text-slate-900">{{ number_format($product->stock, 2) }}</span>
                                <span class="text-xs text-slate-500 ml-1">{{ $product->unit_of_measure_label }}</span>
                            @endif
                        @else
                            <span class="text-slate-400 text-xs">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($product->is_active)
                            <span class="badge-success">Activo</span>
                        @else
                            <span class="badge-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('products.show', $product) }}" class="btn-ghost p-2" title="Ver">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('products.edit', $product) }}" class="btn-ghost p-2" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="text-slate-600 font-medium">No hay productos registrados</p>
                            <a href="{{ route('products.create') }}" class="text-sm text-primary-600 hover:underline mt-1">Crear el primero</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $products->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
