@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<x-page-header title="Notificaciones" subtitle="Centro de notificaciones" :back="route('dashboard')">
    <x-slot:actions>
        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline px-4 py-2 text-sm font-semibold rounded-lg">
                    Marcar todas leídas
                </button>
            </form>
        @endif
    </x-slot:actions>
</x-page-header>

<x-card>
    <div class="divide-y divide-slate-100">
        @forelse($notifications as $notification)
            <div class="px-6 py-4 flex items-start gap-4 {{ $notification->read_at ? '' : 'bg-blue-50/30' }}">
                <div class="flex-shrink-0 mt-1">
                    @php
                        $type = $notification->data['type'] ?? 'unknown';
                        $iconColors = [
                            'invoice_validated' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
                            'invoice_rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
                            'invoice_overdue' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600'],
                            'payment_received' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                            'numbering_range_alert' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
                        ];
                        $colors = $iconColors[$type] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-600'];
                    @endphp
                    <div class="w-10 h-10 rounded-full {{ $colors['bg'] }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $colors['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900">
                        {{ $notification->data['notification_message'] ?? 'Notificación' }}
                    </p>
                    @if($notification->data['invoice_number'] ?? null)
                        <p class="text-xs text-slate-500 mt-1">
                            Factura: <span class="font-mono">{{ $notification->data['invoice_number'] }}</span>
                        </p>
                    @endif
                    <p class="text-xs text-slate-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$notification->read_at)
                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs text-blue-600 hover:underline">Marcar leída</button>
                        </form>
                    @endif
                    @if($notification->data['invoice_id'] ?? null)
                        <a href="{{ route('invoices.show', $notification->data['invoice_id']) }}"
                           class="text-xs text-slate-600 hover:underline">Ver</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-slate-500">
                No tienes notificaciones
            </div>
        @endforelse
    </div>

    <div class="px-6 py-4">
        {{ $notifications->links() }}
    </div>
</x-card>
@endsection