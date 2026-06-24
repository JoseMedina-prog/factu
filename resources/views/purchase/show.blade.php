@extends('layouts.app')

@section('title', 'Compra ' . $purchase->number)

@section('content')
<div class="max-w-7xl mx-auto">
    <x-page-header
        :title="'Compra ' . $purchase->number"
        :subtitle="$purchase->supplier?->name"
        :back="route('purchases.index')"
    >
        <x-slot:actions>
            @can('cancel', $purchase)
                <form action="{{ route('purchases.cancel', $purchase) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-outline" onclick="return confirm('¿Cancelar esta factura de compra?')">
                        Cancelar factura
                    </button>
                </form>
            @endcan
            @if($purchase->hasOutstandingBalance())
                @can('create', App\Models\PurchasePayment::class)
                    <x-button :href="route('purchase-payments.create', ['purchase_invoice_id' => $purchase->id])" variant="primary" icon="cash">
                        Registrar pago
                    </x-button>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="alert-success mb-6 flex items-center gap-2 p-4 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($purchase->status === \App\Models\PurchaseInvoice::STATUS_CANCELLED)
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <p class="font-semibold text-red-900">Esta factura está cancelada.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Items</h3>
                    <span class="text-xs text-slate-500">{{ $purchase->items->count() }} línea(s)</span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="text-right">Cant.</th>
                            <th class="text-right">Precio</th>
                            <th class="text-right">IVA</th>
                            <th class="text-right">Ret.</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        <tr>
                            <td>
                                <span class="font-medium">{{ $item->description }}</span>
                                @if($item->product)
                                    <p class="text-xs text-slate-500">{{ $item->product->name }}</p>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">{{ $item->tax }}%</td>
                            <td class="text-right">{{ $item->retention }}%</td>
                            <td class="text-right font-medium">${{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-6 bg-slate-50 flex justify-end">
                    <div class="w-72 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-600">Subtotal:</span><span class="font-medium">${{ number_format($purchase->subtotal, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-600">IVA:</span><span class="font-medium">${{ number_format($purchase->tax_total, 2) }}</span></div>
                        <div class="flex justify-between text-red-600"><span>Retenciones:</span><span class="font-medium">-${{ number_format($purchase->retention_total, 2) }}</span></div>
                        <div class="flex justify-between pt-2 mt-2 border-t border-slate-300 text-lg font-bold"><span>Total:</span><span class="text-primary-700">${{ number_format($purchase->total, 2) }}</span></div>
                        <div class="flex justify-between pt-2 mt-2 border-t border-slate-200 text-emerald-600"><span>Pagado:</span><span class="font-medium">${{ number_format($purchase->paid_amount, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-600">Saldo:</span><span class="font-semibold {{ $purchase->balance > 0 ? 'text-amber-600' : 'text-slate-500' }}">${{ number_format($purchase->balance, 2) }}</span></div>
                    </div>
                </div>
            </x-card>

            @if($purchase->payments->count() > 0)
                <x-card>
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-900">Pagos realizados</h3>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th>Referencia</th>
                                <th class="text-right">Monto</th>
                                <th>Estado</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td>{{ $payment->method_label }}</td>
                                    <td class="text-xs">{{ $payment->reference ?? '—' }}</td>
                                    <td class="text-right font-semibold">${{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        @php $v = ['confirmed' => 'success', 'pending' => 'warning', 'cancelled' => 'default']; @endphp
                                        <x-badge :variant="$v[$payment->status] ?? 'default'">{{ ucfirst($payment->status) }}</x-badge>
                                    </td>
                                    <td class="text-xs">{{ $payment->creator->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>
            @endif

            @if($purchase->notes)
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h4 class="text-sm font-semibold text-slate-700 mb-1">Notas</h4>
                    <p class="text-sm text-slate-600">{{ $purchase->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <x-card>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Proveedor</h3>
                </div>
                <div class="p-6 text-sm space-y-2">
                    <p class="font-semibold text-slate-900 text-base">{{ $purchase->supplier?->name }}</p>
                    <p class="text-slate-600">{{ $purchase->supplier?->documentDisplay() }}</p>
                    @if($purchase->supplier?->email)<p class="text-slate-600">{{ $purchase->supplier->email }}</p>@endif
                    @if($purchase->supplier?->phone)<p class="text-slate-600">{{ $purchase->supplier->phone }}</p>@endif
                    <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="text-blue-600 hover:underline text-xs mt-2 inline-block">Ver proveedor →</a>
                </div>
            </x-card>

            <x-card>
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Información</h3>
                </div>
                <dl class="p-6 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Estado:</dt>
                        <dd>
                            @php $sv = ['received' => 'success', 'draft' => 'warning', 'cancelled' => 'danger']; @endphp
                            <x-badge :variant="$sv[$purchase->status] ?? 'default'">{{ ucfirst($purchase->status) }}</x-badge>
                        </dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-slate-500">Pago:</dt>
                        <dd>
                            @php $pv = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger', 'overpaid' => 'info']; @endphp
                            <x-badge :variant="$pv[$purchase->payment_status] ?? 'default'">{{ ucfirst($purchase->payment_status) }}</x-badge>
                        </dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-slate-500">Emisión:</dt><dd class="font-medium">{{ $purchase->issue_date->format('d/m/Y') }}</dd></div>
                    @if($purchase->due_date)
                    <div class="flex justify-between"><dt class="text-slate-500">Vencimiento:</dt><dd class="font-medium">{{ $purchase->due_date->format('d/m/Y') }}</dd></div>
                    @endif
                    @if($purchase->received_date)
                    <div class="flex justify-between"><dt class="text-slate-500">Recibida:</dt><dd class="font-medium">{{ $purchase->received_date->format('d/m/Y') }}</dd></div>
                    @endif
                </dl>
            </x-card>
        </div>
    </div>
</div>
@endsection