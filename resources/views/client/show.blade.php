@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">{{ $client->name }}</h1>
        <div class="space-x-2">
            <a href="{{ route('clients.edit', $client) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                Editar
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-500">Documento</dt>
                <dd class="font-medium">{{ $client->document ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Email</dt>
                <dd class="font-medium">{{ $client->email ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Teléfono</dt>
                <dd class="font-medium">{{ $client->phone ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Estado</dt>
                <dd>
                    <span class="px-2 py-1 text-xs rounded {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </dd>
            </div>
            <div class="col-span-2">
                <dt class="text-sm text-gray-500">Dirección</dt>
                <dd class="font-medium">{{ $client->address ?? 'N/A' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Facturas del Cliente</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Número</th>
                    <th class="px-4 py-2 text-left text-sm">Fecha</th>
                    <th class="px-4 py-2 text-left text-sm">Total</th>
                    <th class="px-4 py-2 text-left text-sm">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($client->invoices as $invoice)
                <tr>
                    <td class="px-4 py-2">
                        <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:underline">{{ $invoice->number }}</a>
                    </td>
                    <td class="px-4 py-2">{{ $invoice->issue_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td class="px-4 py-2">${{ number_format($invoice->total, 2) }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 text-xs rounded
                            @if($invoice->status === 'draft') bg-yellow-100 text-yellow-700
                            @elseif($invoice->status === 'pending') bg-blue-100 text-blue-700
                            @elseif($invoice->status === 'sent') bg-green-100 text-green-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay facturas para este cliente</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
