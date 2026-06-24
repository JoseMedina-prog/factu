@extends('layouts.app')

@section('title', 'Pagos')

@section('content')
<x-page-header title="Pagos" subtitle="Registro de pagos y abonos recibidos" :back="route('dashboard')">
    <x-slot:actions>
        @can('viewAny', App\Models\Invoice::class)
            <a href="{{ route('accounts-receivable.index') }}" class="btn btn-outline px-4 py-2 text-sm font-semibold rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Cuentas por cobrar
            </a>
        @endcan
        @can('create', App\Models\Payment::class)
            <a href="{{ route('payments.create') }}" class="btn btn-primary px-4 py-2 text-sm font-semibold rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Registrar pago
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Total recaudado</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">
            ${{ number_format($stats['total_collected'], 0, ',', '.') }}
        </p>
        <p class="text-xs text-slate-400 mt-0.5">En el período seleccionado</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Cantidad de pagos</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">
            {{ number_format($stats['count'], 0) }}
        </p>
        <p class="text-xs text-slate-400 mt-0.5">Pagos confirmados</p>
    </div>
    <div class="stat-card">
        <p class="text-sm font-medium text-slate-500">Período</p>
        <p class="text-base font-bold text-slate-900 mt-1">
            {{ \Carbon\Carbon::parse($stats['period']['start'])->format('d/m/Y') }}
            -
            {{ \Carbon\Carbon::parse($stats['period']['end'])->format('d/m/Y') }}
        </p>
    </div>
</div>

<x-card>
    <div class="px-6 py-4 border-b border-slate-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Método</label>
                <select name="method" class="input">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Payment::METHODS as $key => $label)
                        <option value="{{ $key }}" @selected(request('method') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                <select name="status" class="input">
                    <option value="">Todos</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmado</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelado</option>
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-6 py-3 text-left">Fecha</th>
                    <th class="px-6 py-3 text-left">Cliente</th>
                    <th class="px-6 py-3 text-left">Factura</th>
                    <th class="px-6 py-3 text-right">Monto</th>
                    <th class="px-6 py-3 text-left">Método</th>
                    <th class="px-6 py-3 text-left">Referencia</th>
                    <th class="px-6 py-3 text-left">Estado</th>
                    <th class="px-6 py-3 text-left">Registrado por</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($payments as $payment)
                    <tr>
                        <td class="px-6 py-4">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">{{ $payment->client->name ?? '—' }}</td>
                        <td class="px-6 py-4 font-mono text-xs">
                            @if($payment->invoice)
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-blue-600 hover:underline">
                                    {{ $payment->invoice->number }}
                                </a>
                            @else
                                <span class="text-slate-400">Anticipo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-semibold">${{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">{{ $payment->method_label }}</td>
                        <td class="px-6 py-4 text-xs">{{ $payment->reference ?? '—' }}</td>
                        <td class="px-6 py-4">
                            @php
                                $variants = [
                                    'confirmed' => 'success',
                                    'pending' => 'warning',
                                    'cancelled' => 'default',
                                    'rejected' => 'danger',
                                ];
                            @endphp
                            <x-badge :variant="$variants[$payment->status] ?? 'default'">
                                {{ ucfirst($payment->status) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-600">
                            {{ $payment->creator->name ?? '—' }}
                            @if($payment->created_at)
                                <br><span class="text-slate-400">{{ $payment->created_at->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('view', $payment)
                                <a href="{{ route('payments.show', $payment) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                            @endcan
                            @if($payment->isPending() && auth()->user()->can('confirm', $payment))
                                <form action="{{ route('payments.confirm', $payment) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-emerald-600 hover:underline text-xs">Confirmar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                            No hay pagos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4">
        {{ $payments->links() }}
    </div>
</x-card>
@endsection