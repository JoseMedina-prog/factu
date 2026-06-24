@extends('layouts.app')

@section('title', 'Nueva factura de compra')

@section('content')
<x-page-header title="Nueva factura de compra" subtitle="Registra una factura recibida de un proveedor" :back="route('purchases.index')" />

@php
    $productsJson = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->cost,
            'tax' => (float) $p->tax,
            'unit' => $p->unit_of_measure,
        ];
    })->values()->toJson();
@endphp

<form action="{{ route('purchases.store') }}" method="POST"
      x-data="purchaseForm({{ $productsJson }})">
    @csrf
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Datos del proveedor</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select name="supplier_id" label="Proveedor" required :options="$suppliers->pluck('name', 'id')" :value="old('supplier_id', $supplier?->id)" placeholder="Selecciona un proveedor" />
                    <x-input name="number" label="Número de factura" required placeholder="FE-12345" :value="old('number')" />
                    <x-input name="issue_date" type="date" label="Fecha de emisión" required :value="old('issue_date', now()->toDateString())" />
                    <x-input name="due_date" type="date" label="Fecha de vencimiento" :value="old('due_date')" />
                    <x-input name="received_date" type="date" label="Fecha de recepción" :value="old('received_date', now()->toDateString())" />
                </div>
            </x-card>

            <x-card>
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Items</h3>
                    <button type="button" @click="addRow()" class="btn btn-primary text-xs px-3 py-1.5">
                        + Agregar línea
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="w-2/5">Descripción</th>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio unit.</th>
                                <th class="text-right">IVA %</th>
                                <th class="text-right">Ret %</th>
                                <th class="text-right">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr class="align-top">
                                    <td>
                                        <input type="hidden" :name="`items[${idx}][description]`" x-model="row.description" required>
                                        <input type="text" x-model="row.description" class="input text-sm" placeholder="Descripción" required>
                                    </td>
                                    <td>
                                        <select :name="`items[${idx}][product_id]`" x-model="row.product_id" @change="fillFromProduct(idx)" class="input text-sm">
                                            <option value="">—</option>
                                            <template x-for="p in products" :key="p.id">
                                                <option :value="p.id" x-text="p.name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" :name="`items[${idx}][quantity]`" x-model.number="row.quantity" @input="recalc()" step="0.01" min="0.01" class="input text-sm text-right" required>
                                    </td>
                                    <td>
                                        <input type="number" :name="`items[${idx}][unit_price]`" x-model.number="row.unit_price" @input="recalc()" step="0.01" min="0" class="input text-sm text-right" required>
                                    </td>
                                    <td>
                                        <input type="number" :name="`items[${idx}][tax]`" x-model.number="row.tax" @input="recalc()" step="0.01" min="0" max="100" class="input text-sm text-right">
                                    </td>
                                    <td>
                                        <input type="number" :name="`items[${idx}][retention]`" x-model.number="row.retention" @input="recalc()" step="0.01" min="0" max="100" class="input text-sm text-right">
                                    </td>
                                    <td class="text-right font-semibold pt-3">
                                        $<span x-text="rowSubtotal(row).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                    </td>
                                    <td class="pt-3">
                                        <button type="button" @click="removeRow(idx)" class="btn-ghost p-1 text-red-500" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                @error('items')
                    <p class="form-error p-6 pt-0">{{ $message }}</p>
                @enderror
            </x-card>

            <x-card>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Notas</h3>
                </div>
                <div class="p-6">
                    <textarea name="notes" rows="3" class="input" placeholder="Información adicional sobre esta factura">{{ old('notes') }}</textarea>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Resumen</h3>
                </div>
                <dl class="p-6 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd class="font-medium">$<span x-text="totals.subtotal.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">IVA</dt>
                        <dd class="font-medium">$<span x-text="totals.tax.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></dd>
                    </div>
                    <div class="flex justify-between text-red-600">
                        <dt>Retenciones</dt>
                        <dd class="font-medium">-$<span x-text="totals.retention.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></dd>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-slate-200 text-lg font-bold">
                        <dt>Total a pagar</dt>
                        <dd class="text-primary-700">$<span x-text="totals.total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></dd>
                    </div>
                </dl>
            </x-card>

            <x-card>
                <div class="p-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="auto_register_stock" value="0">
                        <input type="checkbox" name="auto_register_stock" value="1" @checked(old('auto_register_stock', true)) class="mt-1 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-slate-900">Registrar entrada de inventario automáticamente</span>
                            <p class="text-xs text-slate-500 mt-0.5">Si los items están vinculados a productos con inventario, se crearán entradas de stock.</p>
                        </div>
                    </label>
                </div>
            </x-card>

            <div class="flex gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-outline flex-1">Cancelar</a>
                <button type="submit" class="btn btn-primary flex-1">Registrar factura</button>
            </div>
        </div>
    </div>
</form>

<script>
function purchaseForm(products) {
    return {
        products: products || [],
        rows: [
            { product_id: '', description: '', quantity: 1, unit_price: 0, tax: 19, retention: 0 }
        ],
        totals: { subtotal: 0, tax: 0, retention: 0, total: 0 },

        addRow() {
            this.rows.push({ product_id: '', description: '', quantity: 1, unit_price: 0, tax: 19, retention: 0 });
        },

        removeRow(idx) {
            if (this.rows.length === 1) {
                this.rows[0] = { product_id: '', description: '', quantity: 1, unit_price: 0, tax: 19, retention: 0 };
            } else {
                this.rows.splice(idx, 1);
            }
            this.recalc();
        },

        fillFromProduct(idx) {
            const p = this.products.find(x => x.id == this.rows[idx].product_id);
            if (p) {
                this.rows[idx].description = p.name;
                this.rows[idx].unit_price = p.price;
                if (p.tax) this.rows[idx].tax = p.tax;
            }
            this.recalc();
        },

        rowSubtotal(row) {
            return (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0);
        },

        recalc() {
            let subtotal = 0, tax = 0, retention = 0;
            for (const r of this.rows) {
                const sub = this.rowSubtotal(r);
                const taxAmt = sub * (parseFloat(r.tax) || 0) / 100;
                const retAmt = (sub + taxAmt) * (parseFloat(r.retention) || 0) / 100;
                subtotal += sub;
                tax += taxAmt;
                retention += retAmt;
            }
            this.totals = {
                subtotal: subtotal,
                tax: tax,
                retention: retention,
                total: subtotal + tax - retention
            };
        },

        init() {
            this.recalc();
        }
    }
}
</script>
@endsection