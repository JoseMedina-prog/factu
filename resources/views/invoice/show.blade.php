@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if(session('success'))
        <div class="alert-success mb-6 flex items-center gap-2 p-4 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert-error mb-6 flex items-center gap-2 p-4 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if(session('factus_errors'))
        <div class="alert-error mb-6 p-4 rounded-lg">
            <p class="font-semibold mb-2">Errores de validación:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach((array) session('factus_errors') as $field => $errors)
                    @foreach((array) $errors as $error)
                        <li><strong>{{ $field }}:</strong> {{ is_array($error) ? implode(' ', $error) : $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('invoices.index') }}" class="back-btn" aria-label="Volver">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="page-title">Factura {{ $invoice->number ?? 'Borrador' }}</h1>
            </div>
            <p class="page-subtitle">
                @if($invoice->reference_code)
                    Ref: <code class="text-xs">{{ $invoice->reference_code }}</code>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2"
             x-data="{
                openLocalDelete: false,
                openFactusDelete: false,
                 localDeleteUrl: '',
                 factusDeleteUrl: ''
             }">
            @if(!$invoice->reference_code)
                @can('send', $invoice)
                <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Validar en Factus
                    </button>
                </form>
                @endcan

                @can('update', $invoice)
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
                @endcan

                @can('delete', $invoice)
                <button type="button"
                        @click="localDeleteUrl = '{{ route('invoices.destroy', $invoice) }}'; openLocalDelete = true"
                        class="btn btn-outline text-red-600 hover:bg-red-50 hover:border-red-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                    </svg>
                    Eliminar local
                </button>
                @endcan
            @else
                <a href="{{ route('credit-notes.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Crear Nota Crédito
                </a>
            @endif

            @if($invoice->reference_code)
                <form action="{{ route('invoices.refreshStatus', $invoice) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-outline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualizar estado
                    </button>
                </form>

                @php
                    $publicUrl = $invoice->factus_response['data']['links']['public_url']
                        ?? $invoice->factus_response['data']['public_url']
                        ?? null;
                @endphp

                @if($publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-outline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF DIAN
                    </a>
                @else
                    <a href="{{ route('invoices.factusPdf', $invoice) }}" target="_blank" class="btn btn-outline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF local
                    </a>
                @endif

                <a href="{{ route('invoices.factusXml', $invoice) }}" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    XML
                </a>

                @if($invoice->qr_link)
                    <a href="{{ $invoice->qr_link }}" target="_blank" rel="noopener" class="btn btn-outline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        QR DIAN
                    </a>
                @endif

                <button type="button"
                        @click="factusDeleteUrl = '{{ route('invoices.cancel', $invoice) }}'; openFactusDelete = true"
                        class="btn btn-danger"
                        title="Eliminar de Factus (solo funciona si NO se entregó al cliente)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                    </svg>
                    Eliminar de Factus (pre-cliente)
                </button>
            @endif

            @if(!$invoice->reference_code)
                @can('update', $invoice)
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
                @endcan

                @can('delete', $invoice)
                <button type="button"
                        @click="localDeleteUrl = '{{ route('invoices.destroy', $invoice) }}'; openLocalDelete = true"
                        class="btn btn-outline text-red-600 hover:bg-red-50 hover:border-red-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                    </svg>
                    Eliminar local
                </button>
                @endcan
            @endif
        </div>
    </div>

    @if($invoice->cufe)
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 mb-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-green-900 mb-1">Factura validada por la DIAN</h3>
                    <p class="text-sm text-green-700 mb-2">Estado Factus: <strong>{{ $invoice->status_factus ?? 'N/A' }}</strong></p>
                    <div class="bg-white rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-1">CUFE (Código Único de Facturación Electrónica)</p>
                        <code class="text-xs break-all text-slate-900">{{ $invoice->cufe }}</code>
                    </div>
                    @if($invoice->validated_at)
                        <p class="text-xs text-green-700 mt-2">Validada el {{ $invoice->validated_at->format('d/m/Y H:i:s') }}</p>
                    @endif
                    <div class="mt-3 flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-xs text-amber-800">
                            <strong>Documento inmutable.</strong> Una vez validada por la DIAN, esta factura no se puede editar ni eliminar localmente. Para correcciones, crea una <strong>Nota Crédito</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Cliente</h3>
                    <p class="font-semibold text-slate-900">{{ $invoice->client->name }}</p>
                    <p class="text-sm text-slate-600 mt-1">NIT/Doc: {{ $invoice->client->document ?? '—' }}</p>
                    <p class="text-sm text-slate-600">Email: {{ $invoice->client->email ?? '—' }}</p>
                    <p class="text-sm text-slate-600">Tel: {{ $invoice->client->phone ?? '—' }}</p>
                    @if($invoice->client->address)
                        <p class="text-sm text-slate-600 mt-1">{{ $invoice->client->address }}</p>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Información</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Estado:</dt>
                            <dd>
                                @if($invoice->is_validated)
                                    <span class="badge badge-success">Validada DIAN</span>
                                @elseif($invoice->status === 'sent')
                                    <span class="badge badge-warning">Pendiente</span>
                                @elseif($invoice->status === 'cancelled')
                                    <span class="badge badge-danger">Cancelada</span>
                                @else
                                    <span class="badge badge-info">{{ ucfirst($invoice->status) }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Emisión:</dt>
                            <dd class="font-medium">{{ $invoice->issue_date?->format('d/m/Y') ?? 'N/A' }}</dd>
                        </div>
                        @if($invoice->due_date)
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Vencimiento:</dt>
                            <dd class="font-medium">{{ $invoice->due_date->format('d/m/Y') }}</dd>
                        </div>
                        @endif
                        @if($invoice->reference_code)
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Referencia:</dt>
                            <dd class="font-mono text-xs">{{ $invoice->reference_code }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-6 overflow-hidden">
        <table class="table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">IVA %</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <span class="font-medium text-slate-900">{{ $item->description }}</span>
                        @if($item->product)
                            <p class="text-xs text-slate-500 mt-0.5">{{ $item->product->name }}</p>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $item->tax }}%</td>
                    <td class="text-right font-medium">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 bg-slate-50 flex justify-end">
            <div class="w-72 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Subtotal:</span>
                    <span class="font-medium">${{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Impuestos:</span>
                    <span class="font-medium">${{ number_format($invoice->tax_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t border-slate-300 pt-2 mt-2">
                    <span>Total:</span>
                    <span class="text-primary-700">${{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm border-t border-slate-200 pt-2 mt-2">
                    <span class="text-slate-600">Pagado:</span>
                    <span class="font-medium text-emerald-600">${{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Saldo:</span>
                    <span class="font-semibold {{ $invoice->balance > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                        ${{ number_format($invoice->balance, 2) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm pt-1">
                    <span class="text-slate-600">Estado de pago:</span>
                    @php
                        $psVariants = [
                            'paid' => 'success',
                            'partial' => 'warning',
                            'unpaid' => 'danger',
                            'overpaid' => 'info',
                        ];
                        $psLabels = [
                            'paid' => 'Pagada',
                            'partial' => 'Parcial',
                            'unpaid' => 'Pendiente',
                            'overpaid' => 'Sobrepago',
                        ];
                    @endphp
                    <x-badge :variant="$psVariants[$invoice->payment_status] ?? 'default'">
                        {{ $psLabels[$invoice->payment_status] ?? $invoice->payment_status }}
                    </x-badge>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-900">Pagos</h3>
                <p class="text-sm text-slate-500 mt-0.5">Registro de abonos y pagos</p>
            </div>
            @if($invoice->hasOutstandingBalance() && auth()->user()->can('create', App\Models\Payment::class))
                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary px-3 py-1.5 text-xs font-semibold rounded-lg">
                    + Registrar pago
                </a>
            @endif
        </div>

        @if($invoice->payments->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th class="text-right">Monto</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>{{ $payment->method_label }}</td>
                            <td class="text-xs">{{ $payment->reference ?? '—' }}</td>
                            <td class="text-right font-semibold">${{ number_format($payment->amount, 2) }}</td>
                            <td>
                                @php
                                    $pVariants = [
                                        'confirmed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'default',
                                    ];
                                @endphp
                                <x-badge :variant="$pVariants[$payment->status] ?? 'default'">{{ ucfirst($payment->status) }}</x-badge>
                            </td>
                            <td class="text-xs">{{ $payment->creator->name ?? '—' }}</td>
                            <td class="text-right">
                                @can('view', $payment)
                                    <a href="{{ route('payments.show', $payment) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center text-sm text-slate-500">
                Aún no se han registrado pagos para esta factura.
            </div>
        @endif
    </div>

    @if($invoice->notes)
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-1">Notas</h3>
        <p class="text-sm text-slate-600">{{ $invoice->notes }}</p>
    </div>
    @endif

    @if($invoice->integrationLogs->count() > 0)
    <div class="card mt-6">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Historial de Integración con Factus</h3>
            <p class="text-sm text-slate-500 mt-0.5">Registro de llamadas realizadas a la API</p>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Acción</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->integrationLogs as $log)
                <tr>
                    <td class="text-sm">{{ $log->executed_at->format('d/m/Y H:i:s') }}</td>
                    <td class="text-sm font-medium">{{ $log->action }}</td>
                    <td>
                        @if($log->status === 'success')
                            <span class="badge badge-success">Éxito</span>
                        @elseif($log->status === 'error')
                            <span class="badge badge-danger">Error</span>
                        @else
                            <span class="badge badge-warning">Pendiente</span>
                        @endif
                    </td>
                    <td class="text-sm text-slate-600 max-w-md truncate">{{ $log->error_message ?? 'OK' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-5">
        <h3 class="font-semibold text-blue-900 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ¿Cómo funciona el flujo de facturación electrónica?
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="bg-white rounded-lg p-3">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">1</span>
                    <strong class="text-slate-900">Tu App</strong>
                </div>
                <p class="text-slate-600">Guardas la factura en tu base de datos local con estado <code class="text-xs">draft</code>.</p>
            </div>
            <div class="bg-white rounded-lg p-3">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">2</span>
                    <strong class="text-slate-900">Factus (intermediario)</strong>
                </div>
                <p class="text-slate-600">Genera el XML firmado, solicita el CUFE a la DIAN y almacena el documento.</p>
            </div>
            <div class="bg-white rounded-lg p-3">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">3</span>
                    <strong class="text-slate-900">DIAN (autoridad fiscal)</strong>
                </div>
                <p class="text-slate-600">Valida, asigna CUFE y registra en su base de datos nacional. <strong>Es inmutable.</strong></p>
            </div>
        </div>
        <div class="mt-4 bg-white rounded-lg p-4 text-sm">
            <p class="font-semibold text-slate-900 mb-2">Importante sobre "Eliminar de Factus":</p>
            <ul class="space-y-1.5 text-slate-600">
                <li class="flex items-start gap-2">
                    <span class="text-amber-600 font-bold">⚠</span>
                    <span>Este botón <strong>solo funciona</strong> si la factura <strong>NO ha sido entregada al cliente</strong>. Una vez que se envía por correo, la DIAN no permite eliminarla.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-green-600 font-bold">✓</span>
                    <span>Si necesitas corregir una factura ya entregada, usa <strong>Nota Crédito</strong> (botón arriba) para anularla y emite una nueva.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">i</span>
                    <span>El <strong>CUFE</strong> es el identificador único de la factura ante la DIAN. Toda consulta posterior usa ese código.</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- Modal: Eliminar localmente --}}
    <div x-show="openLocalDelete"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="openLocalDelete = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="openLocalDelete = false"
             x-transition
             class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6"
             role="alertdialog"
             aria-modal="true"
             aria-labelledby="modal-local-delete-title">
            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 id="modal-local-delete-title" class="text-lg font-bold text-slate-900">¿Eliminar factura local?</h3>
                    <p class="text-sm text-slate-500 mt-1">Esta acción eliminará la factura <strong class="text-slate-700">{{ $invoice->number }}</strong> de tu base de datos local.</p>
                </div>
                <button @click="openLocalDelete = false" type="button" class="text-slate-400 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-5">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-xs text-amber-800">Esta acción <strong>no se puede deshacer</strong>. Si la factura ya fue validada, esta opción no aparecerá.</p>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="openLocalDelete = false" class="btn btn-outline">
                    Cancelar
                </button>
                <form :action="localDeleteUrl" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                        </svg>
                        Sí, eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Eliminar de Factus --}}
    <div x-show="openFactusDelete"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="openFactusDelete = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="openFactusDelete = false"
             x-transition
             class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6"
             role="alertdialog"
             aria-modal="true"
             aria-labelledby="modal-factus-delete-title">
            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 id="modal-factus-delete-title" class="text-lg font-bold text-slate-900">¿Eliminar factura de Factus?</h3>
                    <p class="text-sm text-slate-500 mt-1">Esta acción afecta a la factura <strong class="text-slate-700">{{ $invoice->number }}</strong>.</p>
                </div>
                <button @click="openFactusDelete = false" type="button" class="text-slate-400 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-3">
                <p class="text-sm font-semibold text-red-900 mb-2">⚠️ Lee esto antes de continuar:</p>
                <ul class="text-sm text-red-800 space-y-1.5">
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">•</span>
                        <span>Solo funciona si la factura <strong>NO ha sido entregada al cliente</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">•</span>
                        <span>La <strong>DIAN seguirá teniendo el registro</strong> (el CUFE no se borra).</span>
                    </li>
                </ul>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5">
                <p class="text-sm font-semibold text-blue-900 mb-2">💡 Si ya se entregó al cliente:</p>
                <p class="text-sm text-blue-800">La única vía legal es crear una <strong>Nota Crédito</strong> que anule la factura original. Usa el botón amarillo arriba.</p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="openFactusDelete = false" class="btn btn-outline">
                    Cancelar
                </button>
                <form :action="factusDeleteUrl" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                        </svg>
                        Sí, eliminar de Factus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
