<?php

namespace App\Services;

use App\Jobs\SendInvoiceJob;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\IntegrationLog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FactusService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $clientId;
    protected string $clientSecret;
    protected int $timeout;
    protected int $retry;
    protected int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.factus.base_url', ''), '/');
        $this->username = (string) config('services.factus.username', '');
        $this->password = (string) config('services.factus.password', '');
        $this->clientId = (string) config('services.factus.client_id', '');
        $this->clientSecret = (string) config('services.factus.client_secret', '');
        $this->timeout = (int) config('services.factus.timeout', 30);
        $this->retry = (int) config('services.factus.retry', 3);
        $this->connectTimeout = (int) config('services.factus.connect_timeout', 5);
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl)
            && !empty($this->username)
            && !empty($this->password)
            && !empty($this->clientId)
            && !empty($this->clientSecret);
    }

    protected function http(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retry, function (int $attempt) {
                return [100, 500, 1500][$attempt - 1] ?? 1500;
            }, throw: false)
            ->acceptJson();
    }

    public function authenticate(): array
    {
        $response = $this->http()
            ->asForm()
            ->post($this->baseUrl . '/oauth/token', [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->username,
                'password' => $this->password,
            ]);

        return $response->json() ?? [];
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = $this->http()
            ->asForm()
            ->post($this->baseUrl . '/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
            ]);

        return $response->json() ?? [];
    }

    public function getToken(): ?string
    {
        return cache()->remember('factus:access_token', 3500, function () {
            $auth = $this->authenticate();

            if (!empty($auth['access_token'])) {
                if (!empty($auth['refresh_token'])) {
                    cache()->put('factus:refresh_token', $auth['refresh_token'], 86400 * 30);
                }
                return $auth['access_token'];
            }

            $refresh = cache()->get('factus:refresh_token');
            if ($refresh) {
                $auth = $this->refreshToken($refresh);
                if (!empty($auth['access_token'])) {
                    return $auth['access_token'];
                }
            }

            Log::warning('FactusService: authentication returned no token', [
                'response' => $auth,
            ]);
            return null;
        });
    }

    public function forgetToken(): void
    {
        cache()->forget('factus:access_token');
    }

    protected function authed(): PendingRequest
    {
        $token = $this->getToken();

        if (empty($token)) {
            $this->forgetToken();
            throw new \RuntimeException('No se pudo autenticar con Factus. Verifica las credenciales.');
        }

        return $this->http()
            ->withToken($token)
            ->asJson()
            ->acceptJson();
    }

    public function buildInvoicePayload(Invoice $invoice): array
    {
        $invoice->loadMissing('client', 'items.product', 'tenant');

        $client = $invoice->client;
        $isCompany = $client && $client->document && Str::of($client->document)->upper()->startsWith(['NIT']);
        $documentCode = $isCompany ? '31' : '13';

        $nitParts = $isCompany ? $this->splitNit($client->document) : null;

        $payload = [
            'reference_code' => $invoice->reference_code ?: ('FACT-' . $invoice->tenant_id . '-' . $invoice->id . '-' . time()),
            'document' => '01',
            'operation_type' => '10',
            'send_email' => true,
            'customer' => [
                'identification_document_code' => $documentCode,
                'identification' => $client ? ($nitParts['number'] ?? $client->document ?? '222222222222') : '222222222222',
                'legal_organization_code' => $isCompany ? '1' : '2',
                'tribute_code' => '01',
                'company' => $isCompany ? $client->name : null,
                'names' => $isCompany ? null : $client->name,
                'address' => $client?->address,
                'email' => $client?->email,
                'phone' => $client?->phone,
            ],
            'items' => $invoice->items->map(function ($item) {
                $rate = (float) $item->tax;
                $taxes = [];
                if ($rate > 0) {
                    $taxes[] = [
                        'code' => '01',
                        'rate' => number_format($rate, 2, '.', ''),
                    ];
                }

                return [
                    'code_reference' => (string) ($item->product_id ?? $item->id),
                    'name' => $item->description,
                    'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                    'discount_rate' => '0.00',
                    'price' => number_format((float) $item->unit_price, 2, '.', ''),
                    'unit_measure_code' => '94',
                    'standard_code' => '999',
                    'taxes' => $taxes,
                ];
            })->values()->all(),
        ];

        if ($invoice->due_date) {
            $payload['payment_details'] = [[
                'payment_form' => '2',
                'payment_method_code' => '42',
                'amount' => number_format((float) $invoice->total, 2, '.', ''),
                'due_date' => $invoice->due_date->toDateString(),
            ]];
        } else {
            $payload['payment_details'] = [[
                'payment_form' => '1',
                'payment_method_code' => '42',
                'amount' => number_format((float) $invoice->total, 2, '.', ''),
            ]];
        }

        if ($nitParts) {
            $payload['customer']['dv'] = $nitParts['dv'];
        }

        if ($invoice->notes) {
            $payload['observation'] = Str::limit($invoice->notes, 250, '');
        }

        return $payload;
    }

    protected function splitNit(?string $nit): ?array
    {
        if (empty($nit)) {
            return null;
        }

        $clean = preg_replace('/[^0-9\-]/', '', $nit);
        if (str_contains($clean, '-')) {
            [$number, $dv] = explode('-', $clean, 2);
            return ['number' => $number, 'dv' => $dv];
        }

        return ['number' => $clean, 'dv' => null];
    }

    public function validateInvoice(Invoice $invoice): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'config_missing',
                'message' => 'Factus no está configurado. Agrega las credenciales en .env',
            ];
        }

        $log = IntegrationLog::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'provider' => 'factus',
            'action' => 'validate_invoice',
            'status' => 'pending',
            'executed_at' => now(),
        ]);

        try {
            $payload = $this->buildInvoicePayload($invoice);
            $invoice->update(['reference_code' => $payload['reference_code']]);
            $log->update(['request_payload' => $payload]);

            $response = $this->authed()
                ->post($this->baseUrl . '/v2/bills/validate', $payload);

            $responseData = $response->json() ?? [];

            if ($response->failed()) {
                $message = $responseData['message'] ?? 'HTTP ' . $response->status();
                $errors = $responseData['errors'] ?? [];

$log->update([
                'response_payload' => $responseData,
                'status' => 'error',
                'error_message' => is_array($errors) ? json_encode($errors) : $message,
            ]);

            event(new \App\Events\InvoiceRejected($invoice, is_array($errors) ? $errors : [], $message));

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $message,
                'errors' => $errors,
                'response' => $responseData,
            ];
            }

            $data = $responseData['data'] ?? $responseData;
            $bill = $data['bill'] ?? $data;
            $cufe = $bill['cufe'] ?? $data['cufe'] ?? null;
            $links = $bill['links'] ?? $data['links'] ?? [];
            $qr = $links['qr'] ?? $bill['qr'] ?? $bill['qr_link'] ?? null;
            $publicUrl = $links['public_url'] ?? $bill['public_url'] ?? $data['public_url'] ?? null;
            $status = $bill['status'] ?? $data['status'] ?? 'VALIDATED';
            $numberingRangeId = $bill['numbering_range']['id'] ?? $data['numbering_range']['id'] ?? null;

            $invoice->update([
                'status' => 'sent',
                'is_validated' => true,
                'cufe' => $cufe,
                'qr_link' => $qr,
                'status_factus' => $status,
                'external_id' => (string) ($numberingRangeId ?? $bill['id'] ?? $data['id'] ?? $invoice->id),
                'validated_at' => now(),
                'factus_response' => $responseData,
            ]);

            $log->update([
                'response_payload' => $responseData,
                'status' => 'success',
            ]);

            event(new \App\Events\InvoiceValidated($invoice->fresh(), $responseData));

            return [
                'success' => true,
                'cufe' => $cufe,
                'qr' => $qr,
                'status' => $status,
                'public_url' => $publicUrl,
                'bill' => $bill,
                'reference_code' => $payload['reference_code'],
            ];
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('FactusService validateInvoice Error: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function sendInvoice(Invoice $invoice): array
    {
        return $this->validateInvoice($invoice);
    }

    public function dispatchSendInvoice(Invoice $invoice): void
    {
        SendInvoiceJob::dispatch($invoice);
    }

    public function checkInvoiceStatus(Invoice $invoice): array
    {
        if (!$invoice->reference_code && !$invoice->external_id) {
            return ['error' => 'Invoice has not been sent yet'];
        }

        try {
            $referenceCode = $invoice->reference_code ?? $invoice->external_id;
            $response = $this->authed()
                ->get($this->baseUrl . '/v1/bills/show/' . urlencode($referenceCode));

            return $response->json() ?? ['error' => 'Empty response'];
        } catch (\Throwable $e) {
            Log::error('FactusService Status Check Error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function downloadInvoicePdf(Invoice $invoice): ?string
    {
        if (!$invoice->reference_code) {
            return null;
        }

        $publicUrl = $invoice->factus_response['data']['links']['public_url']
            ?? $invoice->factus_response['data']['public_url']
            ?? null;

        $endpoints = array_filter([
            $publicUrl,
            $this->baseUrl . '/v2/bills/download-pdf/' . urlencode($invoice->reference_code),
            $this->baseUrl . '/v1/bills/download-pdf/' . urlencode($invoice->reference_code),
        ]);

        foreach ($endpoints as $url) {
            try {
                $isExternal = !str_starts_with($url, $this->baseUrl);
                $request = $isExternal ? $this->http()->acceptJson() : $this->authed();

                $response = $request->get($url);

                if ($response->successful()) {
                    $body = $response->body();
                    if (str_starts_with($body, '%PDF')) {
                        return $body;
                    }
                    if (str_contains((string) $response->header('Content-Type'), 'pdf')) {
                        return $body;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FactusService PDF Download attempt failed: ' . $e->getMessage(), ['url' => $url]);
            }
        }
        return null;
    }

    public function downloadInvoiceXml(Invoice $invoice): ?string
    {
        if (!$invoice->reference_code) {
            return null;
        }

        $endpoints = [
            $this->baseUrl . '/v2/bills/download-xml/' . urlencode($invoice->reference_code),
            $this->baseUrl . '/v1/bills/download-xml/' . urlencode($invoice->reference_code),
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = $this->authed()->get($endpoint);

                if ($response->successful()) {
                    return $response->body();
                }
            } catch (\Throwable $e) {
                Log::warning('FactusService XML Download attempt failed: ' . $e->getMessage(), ['url' => $endpoint]);
            }
        }
        return null;
    }

    public function deleteInvoice(Invoice $invoice, bool $force = false): array
    {
        if (!$invoice->reference_code) {
            return ['success' => false, 'message' => 'La factura no ha sido enviada a Factus todavía.'];
        }

        $notifiedToClient = $this->wasNotifiedToClient($invoice);

        if ($notifiedToClient && !$force) {
            return [
                'success' => false,
                'blocked' => true,
                'message' => 'Esta factura ya fue notificada al cliente. La DIAN no permite eliminarla. Debes crear una NOTA CRÉDITO en su lugar.',
                'requires_credit_note' => true,
            ];
        }

        try {
            $response = $this->authed()
                ->delete($this->baseUrl . '/v1/bills/' . urlencode($invoice->reference_code));

            $data = $response->json() ?? [];

            if ($response->successful()) {
                $invoice->update([
                    'status' => 'cancelled',
                    'status_factus' => 'DELETED',
                ]);

                Log::info('FactusService: Invoice ' . $invoice->number . ' eliminada de Factus (no había sido notificada al cliente)', [
                    'invoice_id' => $invoice->id,
                    'reference_code' => $invoice->reference_code,
                ]);

                return [
                    'success' => true,
                    'notified_to_client' => false,
                    'response' => $data,
                ];
            }

            $message = $data['message'] ?? 'Error al eliminar factura';

            if (str_contains(strtolower($message), 'notified')
                || str_contains(strtolower($message), 'cliente')
                || str_contains(strtolower($message), 'notificad')) {
                $invoice->update(['status_factus' => 'NOTIFIED']);
                return [
                    'success' => false,
                    'blocked' => true,
                    'message' => 'La factura ya fue entregada al cliente. La DIAN no permite eliminarla. Debes crear una NOTA CRÉDITO.',
                    'requires_credit_note' => true,
                    'response' => $data,
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $message,
                'response' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('FactusService Delete Error: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'reference_code' => $invoice->reference_code,
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function wasNotifiedToClient(Invoice $invoice): bool
    {
        if ($invoice->status_factus === 'NOTIFIED') {
            return true;
        }

        $response = $invoice->factus_response ?? [];
        $data = $response['data'] ?? [];
        $status = $data['status'] ?? null;

        return in_array($status, ['NOTIFIED', 'DELIVERED'], true);
    }

    public function listNumberingRanges(): array
    {
        try {
            $response = $this->authed()
                ->get($this->baseUrl . '/v1/numbering-ranges');

            return $response->json() ?? ['data' => []];
        } catch (\Throwable $e) {
            Log::error('FactusService List Ranges Error: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Credenciales no configuradas',
            ];
        }

        try {
            $auth = $this->authenticate();

            if (!empty($auth['access_token'])) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con Factus',
                    'token_type' => $auth['token_type'] ?? 'Bearer',
                    'expires_in' => $auth['expires_in'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'No se pudo obtener token de acceso',
                'response' => $auth,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function buildCreditNotePayload(CreditNote $creditNote): array
    {
        $creditNote->loadMissing('invoice.client', 'invoice.tenant');

        $invoice = $creditNote->invoice;
        $client = $invoice?->client;

        return [
            'reference_code' => $creditNote->reference_code ?: ('NC-' . $creditNote->tenant_id . '-' . $creditNote->id . '-' . time()),
            'correction_concept_code' => $creditNote->concept_code ?? '2',
            'billing_reference' => [
                'number' => $invoice?->number,
                'uuid' => $invoice?->cufe,
                'issue_date' => $invoice?->issue_date?->toDateString(),
            ],
            'discrepancies' => [
                [
                    'description' => Str::limit($creditNote->reason ?? 'Anulación de factura', 250, ''),
                ],
            ],
            'customer' => [
                'identification_document_code' => '13',
                'identification' => $client?->document ?? '222222222222',
                'names' => $client?->name ?? 'Consumidor final',
                'address' => $client?->address,
                'email' => $client?->email,
                'phone' => $client?->phone,
            ],
            'items' => $creditNote->items->map(function ($item) {
                $rate = (float) $item->tax;
                $taxes = [];
                if ($rate > 0) {
                    $taxes[] = [
                        'code' => '01',
                        'rate' => number_format($rate, 2, '.', ''),
                    ];
                }
                return [
                    'code_reference' => (string) ($item->product_id ?? $item->id),
                    'name' => $item->description,
                    'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                    'discount_rate' => '0.00',
                    'price' => number_format((float) $item->unit_price, 2, '.', ''),
                    'unit_measure_code' => '94',
                    'standard_code' => '999',
                    'taxes' => $taxes,
                ];
            })->values()->all(),
        ];
    }
}
