@extends('layouts.app')

@section('title', 'Estado de Cuenta')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('clients.index') }}" class="back-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="page-title">Estado de Cuenta</h1>
            <p class="page-subtitle">{{ $client->name }}</p>
        </div>
    </div>
</div>

<div class="max-w-7xl">
    <div class="card mb-6">
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Cliente</p>
                    <p class="font-medium text-slate-900">{{ $client->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Documento</p>
                    <p class="font-medium text-slate-900">{{ $client->document ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Email</p>
                    <p class="font-medium text-slate-900">{{ $client->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Saldo</p>
                    <p class="text-xl font-bold {{ $balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ${{ number_format($balance, 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">Total Facturado</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($totalInvoiced, 2) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">Notas Crédito</p>
            <p class="text-2xl font-bold text-red-600 mt-1">-${{ number_format($totalCredits, 2) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">Balance</p>
            <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                ${{ number_format($balance, 2) }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Facturas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Número</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td class="text-slate-600">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                        <td class="text-slate-900 font-medium">{{ $invoice->number }}</td>
                        <td class="text-right font-medium text-slate-900">${{ number_format($invoice->total, 2) }}</td>
                        <td class="text-right font-medium text-slate-900">${{ number_format($invoice->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-8 text-slate-500">No hay facturas</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="2" class="font-semibold text-slate-900">Total Facturado</td>
                        <td class="text-right font-bold text-slate-900">${{ number_format($totalInvoiced, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($creditNotes->count() > 0)
    <div class="card mt-6">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Notas Crédito</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Número</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNotes as $creditNote)
                    <tr>
                        <td class="text-slate-600">{{ $creditNote->issue_date->format('d/m/Y') }}</td>
                        <td class="text-slate-900 font-medium">{{ $creditNote->number }}</td>
                        <td class="text-right font-medium text-red-600">-${{ number_format($creditNote->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="2" class="font-semibold text-slate-900">Total Notas Crédito</td>
                        <td class="text-right font-bold text-red-600">-${{ number_format($totalCredits, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
