<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clientService
    ) {}

    public function index(Request $request): View
    {
        $clients = Client::withCount('invoices')
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->orderBy('name')
            ->paginate(15);

        return view('client.index', compact('clients'));
    }

    public function create(Request $request): View
    {
        return view('client.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $this->clientService->create($request);
        return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente');
    }

    public function show(Request $request, Client $client): View
    {
        $client->load('invoices');
        return view('client.show', compact('client'));
    }

    public function edit(Request $request, Client $client): View
    {
        return view('client.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->clientService->update($client, $request);
        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($this->clientService->delete($client)) {
            return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente');
        }

        return redirect()->route('clients.index')->with('error', 'No se puede eliminar el cliente porque tiene facturas asociadas');
    }
}
