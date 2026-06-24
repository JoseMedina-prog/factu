<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
    <div class="flex items-center justify-between px-6 py-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">@yield('title', 'Dashboard')</h1>
        </div>
        <div class="flex items-center gap-3">
            @yield('header-actions')

            @auth
                @php
                    $unreadCount = auth()->user()->unreadNotifications()->count();
                    $recentNotifications = auth()->user()->notifications()->latest()->limit(8)->get();
                @endphp

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                            class="relative p-2 rounded-lg hover:bg-slate-100 transition"
                            aria-label="Notificaciones">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($unreadCount > 0)
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-slate-200 max-h-[480px] overflow-y-auto"
                         style="display: none;">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-semibold text-slate-900 text-sm">Notificaciones</h3>
                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">
                                        Marcar todas leídas
                                    </button>
                                </form>
                            @endif
                        </div>

                        @forelse($recentNotifications as $notification)
                            <a href="{{ $notification->data['invoice_id'] ? route('invoices.show', $notification->data['invoice_id']) : '#' }}"
                               class="block px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50/30' }}">
                                <p class="text-sm font-medium text-slate-900">
                                    {{ $notification->data['notification_message'] ?? 'Notificación' }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-slate-500">
                                No hay notificaciones
                            </div>
                        @endforelse
                    </div>
                </div>
            @endauth
        </div>
    </div>
</header>