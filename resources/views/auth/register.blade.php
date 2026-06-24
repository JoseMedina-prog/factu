<x-layouts.guest>
    <x-slot:title>Registro - Factu</x-slot:title>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="/" class="inline-flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center">
                        <x-icon name="invoice" class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-2xl font-bold text-slate-900">Factu</span>
                </a>
                <h1 class="text-xl font-semibold text-slate-900">Crea tu cuenta</h1>
                <p class="text-slate-500 mt-1">Comienza a gestionar tu negocio</p>
            </div>

            <x-card>
                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <x-input
                        name="name"
                        label="Nombre"
                        placeholder="Tu nombre"
                        icon="user"
                        required
                        autofocus
                        autocomplete="name"
                    />

                    <x-input
                        name="email"
                        label="Email"
                        type="email"
                        placeholder="tu@correo.com"
                        icon="mail"
                        required
                        autocomplete="email"
                    />

                    <x-input
                        name="password"
                        label="Contraseña"
                        type="password"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    />

                    <x-input
                        name="password_confirmation"
                        label="Confirmar Contraseña"
                        type="password"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    />

                    <hr class="border-slate-200">

                    <x-input
                        name="company_name"
                        label="Nombre de la Empresa"
                        placeholder="Mi Empresa SAS"
                        required
                    />

                    <x-input
                        name="company_nit"
                        label="NIT"
                        placeholder="901234567-8"
                        required
                    />

                    <x-button type="submit" variant="primary" :block="true" size="lg">
                        Crear Cuenta
                    </x-button>
                </form>
            </x-card>

            <p class="text-center text-sm text-slate-600 mt-6">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline">Inicia sesión</a>
            </p>
        </div>
    </div>
</x-layouts.guest>
