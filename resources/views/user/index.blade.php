@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Gestiona los usuarios del sistema</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary px-5 py-2.5 text-sm font-semibold rounded-lg shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Usuario
        </a>
    </div>
</div>

<div x-data="{ openDelete: false, deleteUrl: '', deleteName: '' }">
    <div class="card">
        <div class="p-4 border-b border-slate-100">
            <form method="GET" class="flex flex-wrap gap-3">
                <select name="tenant_id" class="input w-auto" onchange="this.form.submit()">
                    <option value="">Todas las empresas</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Empresa</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-semibold text-blue-600">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <span class="font-medium text-slate-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-slate-600">{{ $user->email }}</td>
                        <td>
                            <span class="text-slate-700">{{ $user->tenant?->name ?? 'Sin empresa' }}</span>
                        </td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge badge-info">Admin</span>
                            @else
                                <span class="badge badge-neutral">Staff</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-success">Activo</span>
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost p-2" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if($user->id !== auth()->id())
                                    <button type="button"
                                            @click="deleteUrl = '{{ route('admin.users.destroy', $user) }}'; deleteName = '{{ $user->name }}'; openDelete = true"
                                            class="btn btn-ghost p-2 text-red-600 hover:bg-red-50"
                                            title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                    </svg>
                                </div>
                                <p class="text-slate-600 font-medium">No hay usuarios registrados</p>
                                <a href="{{ route('admin.users.create') }}" class="text-sm text-blue-600 hover:underline mt-1">Crear el primero</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $users->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- Modal: Eliminar usuario --}}
    <div x-show="openDelete"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="openDelete = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="openDelete = false"
             x-transition
             class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6"
             role="alertdialog"
             aria-modal="true">
            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900">¿Eliminar usuario?</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Vas a eliminar a <strong class="text-slate-700" x-text="deleteName"></strong>. Esta acción no se puede deshacer.
                    </p>
                </div>
                <button @click="openDelete = false" type="button" class="text-slate-400 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-5">
                <p class="text-xs text-red-800">El usuario perderá acceso al sistema inmediatamente. Si tiene facturas o datos asociados, podrían quedar inconsistentes.</p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="openDelete = false" class="btn btn-outline">
                    Cancelar
                </button>
                <form :action="deleteUrl" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                        </svg>
                        Sí, eliminar usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
