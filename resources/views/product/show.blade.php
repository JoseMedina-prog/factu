@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
        <a href="{{ route('products.edit', $product) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Editar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-500">Tipo</dt>
                <dd>
                    <span class="px-2 py-1 text-xs rounded {{ $product->type === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ $product->type === 'product' ? 'Producto' : 'Servicio' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Estado</dt>
                <dd>
                    <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Precio</dt>
                <dd class="font-medium text-lg">${{ number_format($product->price, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Impuesto</dt>
                <dd class="font-medium">{{ $product->tax }}%</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Precio con Impuesto</dt>
                <dd class="font-medium text-lg">${{ number_format($product->price_with_tax, 2) }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-sm text-gray-500">Descripción</dt>
                <dd class="mt-1">{{ $product->description ?? 'Sin descripción' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
