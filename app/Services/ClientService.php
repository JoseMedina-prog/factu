<?php

namespace App\Services;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function create(StoreClientRequest $request): Client
    {
        $tenantId = auth()->user()->tenant_id;
        $client = DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();
            $data['tenant_id'] = $tenantId;
            return Client::create($data);
        });

        Cache::forget("tenant:{$tenantId}:active_clients");

        return $client;
    }

    public function update(Client $client, UpdateClientRequest $request): Client
    {
        $tenantId = $client->tenant_id;
        $client = DB::transaction(function () use ($client, $request) {
            $client->update($request->validated());
            return $client->fresh();
        });

        Cache::forget("tenant:{$tenantId}:active_clients");

        return $client;
    }

    public function delete(Client $client): bool
    {
        $tenantId = $client->tenant_id;
        $result = DB::transaction(function () use ($client) {
            return $client->delete();
        });

        if ($result) {
            Cache::forget("tenant:{$tenantId}:active_clients");
        }

        return $result;
    }

    public function getActiveClients()
    {
        $tenantId = auth()->user()->tenant_id;

        return Cache::remember("tenant:{$tenantId}:active_clients", 3600, function () {
            return Client::active()->orderBy('name')->get();
        });
    }

    public function getClientsWithInvoices()
    {
        return Client::has('invoices')->withCount('invoices')->orderBy('name')->get();
    }
}
