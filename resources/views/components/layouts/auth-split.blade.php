<x-layouts.guest>
    <div class="min-h-screen flex">
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 p-12 flex-col justify-between relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="100" cy="100" r="80" fill="white"/>
                    <circle cx="300" cy="200" r="120" fill="white"/>
                    <circle cx="200" cy="350" r="60" fill="white"/>
                </svg>
            </div>

            <div class="relative z-10">
                <a href="/" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <x-icon name="invoice" class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-2xl font-bold text-white">Factu</span>
                </a>
            </div>

            <div class="relative z-10 space-y-6">
                <h1 class="text-4xl font-bold text-white leading-tight">
                    {{ $heroTitle ?? 'Gestiona tu negocio' }}<br>{{ $heroSubtitle ?? 'con facturación electrónica' }}
                </h1>
                <p class="text-primary-100 text-lg max-w-md">
                    {{ $heroDescription ?? 'La plataforma ideal para empresas colombianas. Fácil, rápida y preparada para la DIAN.' }}
                </p>
            </div>

            {{ $heroFooter ?? '' }}
        </div>

        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center">
                        <x-icon name="invoice" class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-2xl font-bold text-slate-900">Factu</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.guest>
