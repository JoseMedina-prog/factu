<x-layouts.guest>
    <x-slot:title>Iniciar Sesión - Factu</x-slot:title>

    <x-layouts.auth-split>
        <div class="text-center lg:text-left mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Bienvenido de nuevo</h2>
            <p class="text-slate-500 mt-1">Ingresa tus credenciales para continuar</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <x-input
                name="email"
                label="Correo electrónico"
                type="email"
                placeholder="correo@ejemplo.com"
                icon="mail"
                required
                autofocus
                autocomplete="username"
            />

            <x-input
                name="password"
                label="Contraseña"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            />

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-slate-600">Recordarme</span>
                </label>
            </div>

            <x-button type="submit" variant="primary" :block="true" size="lg">
                Iniciar Sesión
            </x-button>
        </form>

        <div class="mt-8">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-slate-100 text-slate-500">¿Nuevo en Factu?</span>
                </div>
            </div>

            <x-button :href="route('register')" variant="primary" :block="true" size="lg" class="mt-4">
                Crear una cuenta
            </x-button>
        </div>
    </x-layouts.auth-split>
</x-layouts.guest>
