@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Empresas</h1>
            <p class="page-subtitle">Gestiona las empresas registradas en el sistema</p>
        </div>
        <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary px-5 py-2.5 text-sm font-semibold rounded-lg shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Empresa
        </a>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>NIT</th>
                    <th>Contacto</th>
                    <th>Usuarios</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                <span class="text-sm font-bold text-blue-600">{{ substr($tenant->name, 0, 2) }}</span>
                            </div>
                            <span class="font-medium text-slate-900">{{ $tenant->name }}</span>
                        </div>
                    </td>
                    <td class="text-slate-600">{{ $tenant->nit }}</td>
                    <td>
                        <div class="text-slate-600 text-sm">
                            @if($tenant->email)
                                <div class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $tenant->email }}
                                </div>
                            @endif
                            @if($tenant->phone)
                                <div class="flex items-center gap-1 mt-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $tenant->phone }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-neutral">{{ $tenant->users->count() }} usuarios</span>
                    </td>
                    <td>
                        @if($tenant->is_active)
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-danger">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-ghost p-2" title="Editar">
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
                            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <p class="text-slate-600 font-medium">No hay empresas registradas</p>
                            <p class="text-sm text-slate-500 mt-1">Crea la primera empresa para comenzar</p>
                            <a href="{{ route('admin.tenants.create') }}" class="text-sm text-blue-600 hover:underline mt-2">Crear empresa</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenants->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $tenants->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
