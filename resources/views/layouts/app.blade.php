<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Factu') }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📄</text></svg>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased">
    <div class="min-h-screen flex">
        @auth
            @include('layouts.partials.sidebar')
            <div class="flex-1 flex flex-col min-h-screen ml-64">
                @include('layouts.partials.topbar')
                <main class="flex-1 p-6">
                    @if(session('success'))
                        <div class="alert-success mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert-error mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif
                    @yield('content')
                </main>
                <footer class="bg-white border-t border-slate-200 px-6 py-4">
                    <div class="flex justify-between items-center text-sm text-slate-500">
                        <span>&copy; {{ date('Y') }} Factu</span>
                        <span>SaaS de Facturación para Colombia</span>
                    </div>
                </footer>
            </div>

            @include('layouts.partials.fab')
        @else
            {{ $slot }}
        @endauth
    </div>

    @stack('scripts')
</body>
</html>
