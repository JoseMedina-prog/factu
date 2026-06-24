@extends('layouts.app')

@section('title', 'Nueva Factura')

@section('content')
<x-page-header title="Nueva Factura" subtitle="Crear un nuevo documento de facturación" :back="route('invoices.index')" />

<form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Información del Documento</h2>
                </div>
                <div class="p-6">
                    <div class="form-group">
                        <label class="label label-required">Cliente</label>
                        <select name="client_id" required class="input @error('client_id') input-error @enderror">
                            <option value="">Seleccionar cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} ({{ $client->document ?? 'sin documento' }})
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Fecha de Emisión</label>
                            <input type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" class="input">
                        </div>

                        <div class="form-group">
                            <label class="label">Fecha de Vencimiento</label>
                            <input type="date" name="due_date" value="{{ old('due_date') }}" class="input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Notas</label>
                        <textarea name="notes" rows="2" class="input" placeholder="Notas o comentarios adicionales">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Ítems de la factura</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Selecciona un producto para autocompletar o agrega ítems libres</p>
                    </div>
                    <x-button type="button" variant="outline" size="sm" icon="plus" id="addItem">Agregar ítem</x-button>
                </div>
                <div class="p-6">
                    <div id="itemsContainer" class="space-y-3">
                        @php $oldItems = old('items', [[]]); @endphp
                        @foreach($oldItems as $index => $oldItem)
                            @include('invoice._item_row', ['index' => $index, 'item' => $oldItem, 'products' => $products])
                        @endforeach
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
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
                            <span class="text-xl font-bold text-primary-600" id="total">$0.00</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Acciones</h2>
                </div>
                <div class="p-6 space-y-3">
                    <x-button :href="route('invoices.index')" variant="outline" :block="true" icon="x">Cancelar</x-button>
                    <x-button type="submit" name="action" value="draft" variant="secondary" :block="true" icon="inbox">Guardar como Borrador</x-button>
                    <x-button type="submit" name="action" value="pending" variant="primary" :block="true" icon="send">Crear y Enviar</x-button>
                </div>
            </x-card>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('itemsContainer');
    const addBtn = document.getElementById('addItem');
    const products = @json($productsForJs);
    const tpl = document.getElementById('itemRowTemplate');

    let itemIndex = container.querySelectorAll('.item-row').length;

    function refreshListeners(row) {
        const productSelect = row.querySelector('.product-select');
        if (productSelect) {
            productSelect.addEventListener('change', function () {
                const id = this.value;
                if (id && products[id]) {
                    row.querySelector('.description').value = products[id].name;
                    row.querySelector('.unit-price').value = products[id].price.toFixed(2);
                    row.querySelector('.tax').value = products[id].tax.toFixed(2);
                }
                updateRowSubtotal(row);
                calculateTotals();
            });
        }
    }

    function updateRowSubtotal(row) {
        const qty = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat(row.querySelector('.unit-price').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.subtotal-display').textContent = '$' + subtotal.toFixed(2);
    }

    function calculateTotals() {
        let subtotal = 0, taxTotal = 0;
        container.querySelectorAll('.item-row').forEach(function (row) {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.unit-price').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax').value) || 0;
            const rowSubtotal = qty * price;
            subtotal += rowSubtotal;
            taxTotal += rowSubtotal * (taxRate / 100);
        });
        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('taxTotal').textContent = '$' + taxTotal.toFixed(2);
        document.getElementById('total').textContent = '$' + (subtotal + taxTotal).toFixed(2);
    }

    addBtn.addEventListener('click', function () {
        const clone = tpl.content.cloneNode(true);
        const row = clone.querySelector('.item-row');
        row.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace('__INDEX__', itemIndex);
        });
        container.appendChild(row);
        const appendedRow = container.lastElementChild;
        refreshListeners(appendedRow);
        itemIndex++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item')) {
            if (container.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
                calculateTotals();
            }
        }
    });

    container.addEventListener('input', function (e) {
        if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') || e.target.classList.contains('tax')) {
            updateRowSubtotal(e.target.closest('.item-row'));
            calculateTotals();
        }
    });

    container.querySelectorAll('.item-row').forEach(refreshListeners);
    calculateTotals();
});
</script>

<template id="itemRowTemplate">
    <div class="item-row bg-slate-50 rounded-xl p-4 border border-slate-200">
        <div class="flex items-start gap-3">
            <div class="flex-1 space-y-3">
                <div class="form-group !mb-0">
                    <label class="text-xs text-slate-500 mb-1 block">Producto (opcional)</label>
                    <select class="input product-select" name="items[__INDEX__][product_id]">
                        <option value="">— Ítem libre (sin producto) —</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ $product->price }}"
                                    data-tax="{{ $product->tax }}">
                                {{ $product->name }} — ${{ number_format($product->price, 0) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group !mb-0">
                    <label class="text-xs text-slate-500 mb-1 block">Descripción</label>
                    <input type="text" name="items[__INDEX__][description]" placeholder="Descripción del ítem" required class="input description">
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-12 gap-3">
                    <div class="col-span-1 sm:col-span-2">
                        <label class="text-xs text-slate-500 mb-1 block">Cantidad</label>
                        <input type="number" name="items[__INDEX__][quantity]" step="0.01" min="0.01" value="1" required class="input quantity">
                    </div>
                    <div class="col-span-1 sm:col-span-3">
                        <label class="text-xs text-slate-500 mb-1 block">Precio Unit.</label>
                        <input type="number" name="items[__INDEX__][unit_price]" step="0.01" min="0" required class="input unit-price">
                    </div>
                    <div class="col-span-1 sm:col-span-2">
                        <label class="text-xs text-slate-500 mb-1 block">IVA %</label>
                        <input type="number" name="items[__INDEX__][tax]" step="0.01" min="0" max="100" value="0" class="input tax">
                    </div>
                    <div class="col-span-1 sm:col-span-4">
                        <label class="text-xs text-slate-500 mb-1 block">Subtotal</label>
                        <div class="input bg-white font-medium subtotal-display flex items-center">$0.00</div>
                    </div>
                    <div class="col-span-1 sm:col-span-1 flex items-end">
                        <button type="button" class="remove-item w-full inline-flex items-center justify-center p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50:bg-red-900/20 rounded-lg transition-colors" title="Eliminar ítem" aria-label="Eliminar ítem">
                            <x-icon name="trash" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
@endpush
@endsection
