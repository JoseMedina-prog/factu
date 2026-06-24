@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white rounded-lg shadow p-8">
        <h2 class="text-2xl font-bold text-center mb-6">Cerrar Sesión</h2>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <p class="text-center mb-4">¿Estás seguro de que quieres cerrar sesión?</p>

            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700">
                Cerrar Sesión
            </button>
        </form>
    </div>
</div>
@endsection
