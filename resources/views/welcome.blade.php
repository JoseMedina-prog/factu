<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factu - SaaS de Facturación para Colombia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 antialiased">
    <div class="min-h-screen">
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-sm border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <a href="/" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-slate-900">Factu</span>
                    </a>
                    <nav class="flex items-center gap-3">
                        @auth
                            <x-button :href="route('dashboard')" variant="primary" size="sm">Ir al Dashboard</x-button>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 px-3 py-2">
                                Iniciar Sesión
                            </a>
                            <x-button :href="route('register')" variant="primary" size="sm">Crear Cuenta</x-button>
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center max-w-3xl mx-auto">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 text-primary-700 text-sm font-medium mb-6">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                            </span>
                            Preparado para facturación electrónica
                        </div>
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-slate-900 leading-tight">
                            Facturación electrónica para
                            <span class="text-primary-600">empresas colombianas</span>
                        </h1>
                        <p class="mt-6 text-lg text-slate-600 max-w-2xl mx-auto">
                            Gestiona tus clientes, productos y facturas de forma eficiente.
                            Sistema multiempresa diseñado para PYMES colombianas.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-4">
                            @auth
                                <x-button :href="route('dashboard')" variant="primary" size="lg">Ir al Dashboard</x-button>
                            @else
                                <x-button :href="route('register')" variant="primary" size="lg">Comenzar Gratis</x-button>
                                <x-button :href="route('login')" variant="outline" size="lg">Iniciar Sesión</x-button>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-20 bg-slate-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold text-slate-900">Todo lo que necesitas</h2>
                        <p class="mt-3 text-slate-600">Herramientas completas para gestionar tu negocio</p>
                    </div>
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-slate-900 mb-3">Gestión de Clientes</h3>
                            <p class="text-slate-600">Administra tu base de clientes de forma eficiente con documentos y contactos.</p>
                        </div>

                        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="w-14 h-14 bg-violet-100 rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-slate-900 mb-3">Productos y Servicios</h3>
                            <p class="text-slate-600">Controla tu catálogo con precios, impuestos y categorías.</p>
                        </div>

                        <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-slate-900 mb-3">Facturación Electrónica</h3>
                            <p class="text-slate-600">Genera facturas electrónicas lista para enviar a la DIAN.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-2 gap-16 items-center">
                        <div>
                            <h2 class="text-3xl font-bold text-slate-900">Multi-empresa</h2>
                            <p class="mt-4 text-lg text-slate-600">
                                Gestiona múltiples empresas desde una sola cuenta. Ideal para contadores y firmas de servicios.
                            </p>
                            <ul class="mt-8 space-y-4">
                                <li class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-slate-700">Usuarios con roles：admin y staff</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-slate-700">Datos aislados por empresa</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-slate-700">Fácil transición entre empresas</span>
                                </li>
                            </ul>
                        </div>
                        <div class="bg-slate-100 rounded-2xl p-8">
                            <div class="space-y-4">
                                <div class="bg-white rounded-lg p-4 shadow-sm flex items-center gap-4">
                                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <span class="text-sm font-bold text-primary-700">EM</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">Empresa Marketing SAS</p>
                                        <p class="text-sm text-slate-500">Activa</p>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm flex items-center gap-4">
                                    <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center">
                                        <span class="text-sm font-bold text-violet-700">CT</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">Consultoría Tech</p>
                                        <p class="text-sm text-slate-500">Activa</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-20 bg-primary-600">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl font-bold text-white">¿Listo para comenzar?</h2>
                    <p class="mt-3 text-lg text-primary-100">Crea tu cuenta gratis y empieza a gestionar tu negocio</p>
                    <div class="mt-8">
                        @auth
                            <x-button :href="route('dashboard')" variant="white" size="lg">Ir al Dashboard</x-button>
                        @else
                            <x-button :href="route('register')" variant="white" size="lg">Crear Cuenta Gratis</x-button>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-slate-900 text-slate-400 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-white font-semibold">Factu</span>
                    </div>
                    <p class="text-sm">&copy; {{ date('Y') }} Factu. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
