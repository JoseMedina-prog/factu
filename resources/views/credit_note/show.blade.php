@extends('layouts.app')

@section('title', 'Nota de Crédito')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('credit-notes.index') }}" class="back-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="page-title">Nota de Crédito</h1>
            <p class="page-subtitle">{{ $creditNote->number }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        @if($creditNote->status === 'draft')
            <button type="button"
                    x-data
                    @click="$dispatch('open-modal', { name: 'approve-credit-note', url: '{{ route('credit-notes.approve', $creditNote) }}', label: '{{ $creditNote->number }}' })"
                    class="btn btn-success px-4 py-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Aprobar
            </button>
            <a href="{{ route('credit-notes.edit', $creditNote) }}" class="btn btn-outline px-4 py-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
        @endif
        @if($creditNote->status !== 'cancelled')
            <button type="button"
                    x-data
                    @click="$dispatch('open-modal', { name: 'cancel-credit-note', url: '{{ route('credit-notes.cancel', $creditNote) }}', label: '{{ $creditNote->number }}' })"
                    class="btn btn-danger px-4 py-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
            </button>
        @endif
    </div>
</div>

<div x-data="{
    open: false,
    action: '',
    url: '',
    label: '',
    openModal(data) {
        this.action = data.name;
        this.url = data.url;
        this.label = data.label;
        this.open = true;
    }
}"
     @open-modal.window="openModal($event.detail)"
     @keydown.escape.window="open = false">
    <div x-show="open"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="open = false"
             x-transition
             class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6"
             role="alertdialog"
             aria-modal="true">
            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                     :class="action === 'approve-credit-note' ? 'bg-emerald-100' : 'bg-red-100'">
                    <svg x-show="action === 'approve-credit-note'" class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="action === 'cancel-credit-note'" class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900"
                        x-text="action === 'approve-credit-note' ? '¿Aprobar nota de crédito?' : '¿Cancelar nota de crédito?'"></h3>
                    <p class="text-sm text-slate-500 mt-1">
                        <span x-show="action === 'approve-credit-note'">Vas a aprobar <strong class="text-slate-700" x-text="label"></strong>. Quedará vigente ante la DIAN.</span>
                        <span x-show="action === 'cancel-credit-note'">Vas a cancelar <strong class="text-slate-700" x-text="label"></strong>. Esta acción no se puede deshacer.</span>
                    </p>
                </div>
                <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div :class="action === 'approve-credit-note' ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'"
                 class="border rounded-lg p-3 mb-5">
                <p :class="action === 'approve-credit-note' ? 'text-emerald-800' : 'text-amber-800'"
                   class="text-xs">
                    <span x-show="action === 'approve-credit-note'">Una vez aprobada, la nota de crédito anulará la factura original ante la DIAN. Verifica que los datos sean correctos.</span>
                    <span x-show="action === 'cancel-credit-note'">La nota de crédito dejará de estar activa y la factura original seguirá registrada en la DIAN.</span>
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false" class="btn btn-outline">Cancelar</button>
                <form :action="url" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            :class="action === 'approve-credit-note' ? 'btn btn-success' : 'btn btn-danger'">
                        <svg x-show="action === 'approve-credit-note'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="action === 'cancel-credit-note'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span x-text="action === 'approve-credit-note' ? 'Sí, aprobar nota' : 'Sí, cancelar nota'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl">
    <div class="card mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ $creditNote->number }}</h2>
                    <p class="text-slate-500 mt-1">Fecha: {{ $creditNote->issue_date->format('d/m/Y') }}</p>
                </div>
                @switch($creditNote->status)
                    @case('draft')
                        <span class="badge badge-warning text-sm px-3 py-1">Borrador</span>
                        @break
                    @case('approved')
                        <span class="badge badge-success text-sm px-3 py-1">Aprobada</span>
                        @break
                    @case('cancelled')
                        <span class="badge badge-danger text-sm px-3 py-1">Cancelada</span>
                        @break
                @endswitch
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-1">Cliente</h3>
                    <p class="font-medium text-slate-900">{{ $creditNote->client->name }}</p>
                    @if($creditNote->client->email)
                        <p class="text-sm text-slate-500">{{ $creditNote->client->email }}</p>
                    @endif
                </div>
                @if($creditNote->invoice)
                <div>
                    <h3 class="text-sm font-medium text-slate-500 mb-1">Factura Relacionada</h3>
                    <a href="{{ route('invoices.show', $creditNote->invoice) }}" class="text-blue-600 hover:underline">
                        {{ $creditNote->invoice->number }}
                    </a>
                </div>
                @endif
            </div>

            @if($creditNote->notes)
            <div class="bg-slate-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-slate-500 mb-1">Notas</h3>
                <p class="text-slate-700">{{ $creditNote->notes }}</p>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Precio</th>
                        <th class="text-right">IVA</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNote->items as $item)
                    <tr>
                        <td class="text-slate-700">{{ $item->description }}</td>
                        <td class="text-center text-slate-600">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right text-slate-600">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right text-slate-600">{{ $item->tax_rate }}%</td>
                        <td class="text-right font-medium text-slate-900">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-200">
            <div class="flex justify-end">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="text-slate-900">${{ number_format($creditNote->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="text-slate-900">${{ number_format($creditNote->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t border-slate-300 pt-2">
                        <span class="text-slate-900">Total</span>
                        <span class="text-blue-600">${{ number_format($creditNote->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
