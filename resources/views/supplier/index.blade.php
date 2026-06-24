@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
<x-page-header title="Proveedores" subtitle="Gestiona tu base de proveedores" :back="null">
    <x-slot:actions>
        <x-button :href="route('suppliers.create')" variant="primary" icon="plus">Nuevo Proveedor</x-button>
    </x-slot:actions>
</x-page-header>

<x-card>
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-input name="search" placeholder="Buscar por nombre o NIT..." :value="request('search')" />
            </div>
            <x-select
                name="status"
                :options="['active' => 'Activos', 'inactive' => 'Inactivos']"
                placeholder="Todos"
                :value="request('status')"
                class="w-auto"
            />
            <button type="submit" class="btn btn-outline">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Contacto</th>
                    <th class="text-right">Saldo pendiente</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                <tr>
                    <td>
                        <a href="{{ route('suppliers.show', $supplier) }}" class="font-medium text-slate-900 hover:text-primary-600">
                            {{ $supplier->name }}
                        </a>
                    </td>
                    <td class="text-slate-600 text-sm">{{ $supplier->documentDisplay() }}</td>
                    <td class="text-slate-600 text-sm">
                        {{ $supplier->contact_name ?? '—' }}
                        @if($supplier->phone)
                            <br><span class="text-xs text-slate-400">{{ $supplier->phone }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if(($supplier->outstanding ?? 0) > 0)
                            <span class="font-semibold text-amber-600">${{ number_format($supplier->outstanding, 0, ',', '.') }}</span>
                        @else
                            <span class="text-slate-400">$0</span>
                        @endif
                    </td>
                    <td>
                        <x-badge :variant="$supplier->is_active ? 'success' : 'default'">
                            {{ $supplier->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn-ghost p-2" title="Ver">
                                <x-icon name="eye" class="w-4 h-4" />
                            </a>
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-ghost p-2" title="Editar">
                                <x-icon name="edit" class="w-4 h-4" />
                            </a>
                            <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn-ghost p-2" title="Nueva factura de compra">
                                <x-icon name="plus" class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <x-empty-state
                            title="No hay proveedores"
                            description="Agrega tu primer proveedor para registrar compras"
                            :action-href="route('suppliers.create')"
                            action-label="Crear el primero"
                        />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($suppliers->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $suppliers->withQueryString()->links() }}
    </div>
    @endif
</x-card>
@endsection