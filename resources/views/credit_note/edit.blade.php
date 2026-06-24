@extends('layouts.app')

@section('title', 'Editar Nota de Crédito')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('credit-notes.index') }}" class="back-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="page-title">Editar Nota de Crédito</h1>
            <p class="page-subtitle">Modificar nota de crédito #{{ $creditNote->number }}</p>
        </div>
    </div>
</div>

<form action="{{ route('credit-notes.update', $creditNote) }}" method="POST" id="creditNoteForm">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Información</h2>
                </div>
                <div class="p-6">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="label label-required">Cliente</label>
                            <select name="client_id" required class="input @error('client_id') input-error @enderror">
                                <option value="">Seleccionar cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $creditNote->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="label">Fecha de Emisión</label>
                            <input type="date" name="issue_date" value="{{ old('issue_date', $creditNote->issue_date?->toDateString()) }}" class="input">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Notas</label>
                            <textarea name="notes" rows="2" class="input" placeholder="Motivo de la nota crédito">{{ old('notes', $creditNote->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Ítems</h2>
                    <button type="button" id="addItem" class="btn btn-sm btn-outline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar ítem
                    </button>
                </div>
                <div class="p-6">
                    <div id="itemsContainer" class="space-y-4">
                        @php
                            $items = old('items') ?? $creditNote->items->map(fn($item) => [
                                'description' => $item->description,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'tax_rate' => $item->tax_rate,
                            ])->toArray();
                        @endphp
                        @foreach($items as $index => $item)
                        <div class="item-row bg-slate-50 rounded-xl p-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-1 space-y-3">
                                    <input type="text" name="items[{{ $index }}][description]" placeholder="Descripción del ítem"
                                           value="{{ $item['description'] ?? '' }}" required class="input">
                                    <div class="flex items-center gap-3">
                                        <div class="w-28">
                                            <label class="text-xs text-slate-500 mb-1 block">Cantidad</label>
                                            <input type="number" name="items[{{ $index }}][quantity]" placeholder="Cant" step="0.01" min="0.01"
                                                   value="{{ $item['quantity'] ?? 1 }}" required class="input quantity">
                                        </div>
                                        <div class="w-36">
                                            <label class="text-xs text-slate-500 mb-1 block">Precio</label>
                                            <input type="number" name="items[{{ $index }}][unit_price]" placeholder="Precio" step="0.01" min="0"
                                                   value="{{ $item['unit_price'] ?? '' }}" required class="input unit-price">
                                        </div>
                                        <div class="w-28">
                                            <label class="text-xs text-slate-500 mb-1 block">IVA %</label>
                                            <input type="number" name="items[{{ $index }}][tax_rate]" placeholder="%" step="0.01" min="0" max="100"
                                                   value="{{ $item['tax_rate'] ?? 0 }}" class="input tax-rate">
                                        </div>
                                        <div class="flex-1">
                                            <label class="text-xs text-slate-500 mb-1 block">Subtotal</label>
                                            <div class="input bg-white font-medium subtotal-display pt-2">$0.00</div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="remove-item mt-7 text-slate-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Resumen</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-medium text-slate-900" id="subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="font-medium text-slate-900" id="taxTotal">$0.00</span>
                    </div>
                    <div class="pt-4 border-t border-slate-200">
                        <div class="flex justify-between">
                            <span class="text-base font-medium text-slate-900">Total</span>
                            <span class="text-xl font-bold text-blue-600" id="total">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Acciones</h2>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('credit-notes.index') }}" class="btn btn-outline w-full py-3 text-sm font-medium rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary w-full py-3 text-sm font-medium rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Actualizar Nota de Crédito
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('itemsContainer');
    const addBtn = document.getElementById('addItem');
    let itemIndex = {{ count($items) }};

    addBtn.addEventListener('click', function() {
        const html = `
            <div class="item-row bg-slate-50 rounded-xl p-4">
                <div class="flex items-start gap-4">
                    <div class="flex-1 space-y-3">
                        <input type="text" name="items[${itemIndex}][description]" placeholder="Descripción del ítem" required class="input">
                        <div class="flex items-center gap-3">
                            <div class="w-28">
                                <label class="text-xs text-slate-500 mb-1 block">Cantidad</label>
                                <input type="number" name="items[${itemIndex}][quantity]" placeholder="Cant" step="0.01" min="0.01" value="1" required class="input quantity">
                            </div>
                            <div class="w-36">
                                <label class="text-xs text-slate-500 mb-1 block">Precio</label>
                                <input type="number" name="items[${itemIndex}][unit_price]" placeholder="Precio" step="0.01" min="0" required class="input unit-price">
                            </div>
                            <div class="w-28">
                                <label class="text-xs text-slate-500 mb-1 block">IVA %</label>
                                <input type="number" name="items[${itemIndex}][tax_rate]" placeholder="%" step="0.01" min="0" max="100" value="0" class="input tax-rate">
                            </div>
                            <div class="flex-1">
                                <label class="text-xs text-slate-500 mb-1 block">Subtotal</label>
                                <div class="input bg-white font-medium subtotal-display pt-2">$0.00</div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="remove-item mt-7 text-slate-400 hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        itemIndex++;
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            if (container.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
                calculateTotals();
            }
        }
    });

    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') || e.target.classList.contains('tax-rate')) {
            updateRowSubtotal(e.target.closest('.item-row'));
            calculateTotals();
        }
    });

    function updateRowSubtotal(row) {
        const qty = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat(row.querySelector('.unit-price').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.subtotal-display').textContent = '$' + subtotal.toFixed(2);
    }

    function calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;

        container.querySelectorAll('.item-row').forEach(function(row) {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.unit-price').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
            const rowSubtotal = qty * price;
            subtotal += rowSubtotal;
            taxTotal += rowSubtotal * (taxRate / 100);
        });

        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('taxTotal').textContent = '$' + taxTotal.toFixed(2);
        document.getElementById('total').textContent = '$' + (subtotal + taxTotal).toFixed(2);
    }

    calculateTotals();
});
</script>
@endpush
@endsection
