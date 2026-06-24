@extends('layouts.app')

@section('title', 'Suscripción inactiva')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Suscripción inactiva</h1>
        <p class="text-slate-600 mb-6">
            La suscripción de tu empresa <strong>{{ $tenant->name }}</strong> ({{ $tenant->plan_label }})
            @if($tenant->plan_expires_at)
                expiró el <strong>{{ $tenant->plan_expires_at->format('d/m/Y') }}</strong>
            @else
                no se encuentra activa
            @endif
            . Renueva tu plan para continuar usando el sistema.
        </p>
        <div class="space-y-2">
            <a href="mailto:soporte@factu.app" class="block w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                Contactar a soporte
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full bg-slate-100 text-slate-700 px-4 py-2 rounded-md hover:bg-slate-200 transition">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</div>
@endsection