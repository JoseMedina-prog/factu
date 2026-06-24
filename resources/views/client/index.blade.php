@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<x-page-header title="Clientes" subtitle="Gestiona tu base de clientes" :back="null">
    <x-slot:actions>
        <x-button :href="route('clients.create')" variant="primary" icon="plus">Nuevo Cliente</x-button>
    </x-slot:actions>
</x-page-header>

<x-card>
    <div class="p-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-input name="search" placeholder="Buscar por nombre..." :value="request('search')" />
            </div>
            <x-select
                name="is_active"
                :options="['1' => 'Activos', '0' => 'Inactivos']"
                placeholder="Todos"
                :value="request('is_active')"
                class="w-auto"
            />
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <caption class="sr-only">Listado de clientes</caption>
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Documento</th>
                    <th scope="col">Email</th>
                    <th scope="col">Facturas</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-slate-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-slate-600">
                                    {{ strtoupper(substr($client->name, 0, 1)) }}
                                </span>
                            </div>
                            <span class="font-medium text-slate-900">{{ $client->name }}</span>
                        </div>
                    </td>
                    <td class="text-slate-600">{{ $client->document ?? 'N/A' }}</td>
                    <td class="text-slate-600">{{ $client->email ?? 'N/A' }}</td>
                    <td>
                        <x-badge variant="neutral">{{ $client->invoices_count }} facturas</x-badge>
                    </td>
                    <td>
                        <x-badge :variant="$client->is_active ? 'success' : 'danger'">
                            {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('clients.show', $client) }}" class="btn-ghost p-2" title="Ver" aria-label="Ver cliente {{ $client->name }}">
                                <x-icon name="eye" class="w-4 h-4" />
                            </a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn-ghost p-2" title="Editar" aria-label="Editar cliente {{ $client->name }}">
                                <x-icon name="edit" class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <x-empty-state
                            title="No hay clientes registrados"
                            description="Comienza agregando tu primer cliente"
                            :action-href="route('clients.create')"
                            action-label="Crear el primero"
                        />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clients->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $clients->withQueryString()->links() }}
    </div>
    @endif
</x-card>
@endsection
