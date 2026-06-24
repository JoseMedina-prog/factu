# Documentación técnica: Creación de facturas y envío a la DIAN en Factu

> Documento generado a partir del código fuente del proyecto.
> Cubre el flujo completo: rutas, controladores, servicios, vistas, modelo, payload
> exacto que se envía a Factus y el Job asíncrono (`SendInvoiceJob`).

---

## 1. Contexto y arquitectura

Factu es una aplicación **Laravel 11 multi-tenant** que se integra con la DIAN
colombiana a través del proveedor **Factus** (intermediario tecnológico autorizado
para facturación electrónica).

```
[ Tu App (Factu) ] ──HTTP──▶ [ API Factus ] ──(interno)──▶ [ DIAN ]
        │                          │                            │
   Guarda en BD local        Genera XML firmado           Valida y asigna
   (estado draft/pending)    Solicita el CUFE              CUFE (inmutable)
```

La aplicación **nunca habla directamente con la DIAN**. Todo pasa por Factus,
que es quien firma digitalmente el XML, solicita el CUFE ante la DIAN y
almacena el documento electrónico resultante.

### Los 3 actores

1. **Tu App (Factu)** — Guarda la factura en BD local, construye el payload,
   llama a la API de Factus y persiste la respuesta (CUFE, QR, estado, etc.).
2. **Factus (intermediario)** — Genera el XML firmado digitalmente, lo envía a
   la DIAN, recibe el CUFE y expone endpoints de consulta, descarga y
   eliminación.
3. **DIAN (autoridad fiscal)** — Valida el documento, asigna el CUFE único y
   lo registra en su base de datos nacional. El documento con CUFE es
   **inmutable**: una vez validado no se puede editar ni eliminar.

---

## 2. Rutas (`routes/web.php`)

Todas las rutas de facturas están protegidas por los middlewares `auth` y
`tenant` (definidos en `routes/web.php:16-46`).

| Método  | URI                                          | Acción del controlador             | Nombre de ruta              |
|---------|----------------------------------------------|------------------------------------|-----------------------------|
| GET     | `/invoices`                                  | `InvoiceController@index`          | `invoices.index`            |
| GET     | `/invoices/create`                           | `InvoiceController@create`         | `invoices.create`           |
| POST    | `/invoices`                                  | `InvoiceController@store`          | `invoices.store`            |
| GET     | `/invoices/{invoice}`                        | `InvoiceController@show`           | `invoices.show`             |
| GET     | `/invoices/{invoice}/edit`                   | `InvoiceController@edit`           | `invoices.edit`             |
| PUT     | `/invoices/{invoice}`                        | `InvoiceController@update`         | `invoices.update`           |
| DELETE  | `/invoices/{invoice}`                        | `InvoiceController@destroy`        | `invoices.destroy`          |
| POST    | `/invoices/{invoice}/send`                   | `InvoiceController@send`           | `invoices.send`             |
| POST    | `/invoices/{invoice}/cancel`                 | `InvoiceController@cancel`         | `invoices.cancel`           |
| POST    | `/invoices/{invoice}/refresh-status`         | `InvoiceController@refreshStatus`  | `invoices.refreshStatus`    |
| GET     | `/invoices/{invoice}/download-pdf`           | `InvoiceController@downloadPdf`    | `invoices.downloadPdf`      |
| GET     | `/invoices/{invoice}/stream-pdf`             | `InvoiceController@streamPdf`      | `invoices.streamPdf`        |
| GET     | `/invoices/{invoice}/factus-pdf`             | `InvoiceController@downloadFactusPdf` | `invoices.factusPdf`     |
| GET     | `/invoices/{invoice}/factus-xml`             | `InvoiceController@downloadFactusXml` | `invoices.factusXml`     |

Adicionalmente se define `Route::resource('invoices', InvoiceController::class)`
que aporta automáticamente las rutas RESTful estándar (index, create, store,
show, edit, update, destroy).

### Middlewares aplicados

- **`auth`** — Requiere usuario autenticado.
- **`tenant`** — Verifica que el usuario tenga `tenant_id` y que el tenant
  esté `is_active`. Implementado en
  `app/Http/Middleware/TenantMiddleware.php`. Además, registra el tenant
  actual en el container: `app()->instance('current_tenant_id', $user->tenant_id)`.
- **`TenantScope`** — Scope global de Eloquent que filtra automáticamente
  todos los registros por `tenant_id` (registrado en
  `app/Models/Invoice.php:52-55` mediante `booted()`).

---

## 3. Controlador: `InvoiceController`

Ubicación: `app/Http/Controllers/Invoice/InvoiceController.php`

Es el **único punto de entrada HTTP** para todo lo relacionado con facturas.
Inyecta por constructor los servicios que necesita (Inyección de Dependencias
de Laravel):

```php
public function __construct(
    protected InvoiceService $invoiceService,   // CRUD local
    protected ClientService $clientService,     // datos de clientes
    protected ProductService $productService,   // datos de productos
    protected FactusService $factusService,     // comunicación con Factus/DIAN
    protected InvoicePdfService $pdfService     // PDF local con DomPDF
) {}
```

### Métodos del controlador

#### `index(Request $request): View`
Lista las facturas con filtros opcionales. Usa el modelo `Invoice` directamente
con `with('client')`, y delega al servicio solo para obtener estadísticas:

```php
$invoices = Invoice::with('client')
    ->when($request->status, fn($q) => $q->where('status', $request->status))
    ->when($request->search, fn($q) => $q->where('number', 'like', '%' . $request->search . '%'))
    ->orderByDesc('created_at')
    ->paginate(15);

$stats = $this->invoiceService->getInvoiceStats();

return view('invoice.index', compact('invoices', 'stats'));
```

#### `create(Request $request): View`
Carga los datos auxiliares que necesita el formulario:

```php
$clients = $this->clientService->getActiveClients();
$products = $this->productService->getActiveProducts();

// Productos en formato clave=>valor para JavaScript (autocompletado)
$productsForJs = $products->mapWithKeys(fn($p) => [$p->id => [
    'name' => $p->name,
    'price' => (float) $p->price,
    'tax' => (float) $p->tax,
]])->all();

return view('invoice.create', compact('clients', 'products', 'productsForJs'));
```

#### `store(StoreInvoiceRequest $request): RedirectResponse`
**Punto de entrada para CREAR una factura (sin enviarla todavía)**.
Valida con `StoreInvoiceRequest` y delega al servicio:

```php
$invoice = $this->invoiceService->create($request);
return redirect()->route('invoices.index')->with('success', 'Factura creada correctamente');
```

#### `show(Request $request, Invoice $invoice): View`
Carga la factura con sus relaciones y la muestra. Aquí **NO** se llama a Factus:

```php
$invoice->load(['client', 'items.product', 'integrationLogs']);
return view('invoice.show', compact('invoice'));
```

#### `edit()` / `update()` / `destroy()`
CRUD estándar. **Importante**: si la factura ya tiene `reference_code` o
`is_validated=true`, no se puede editar ni eliminar localmente — el documento
es inmutable post-DIAN:

```php
if ($invoice->reference_code || $invoice->is_validated) {
    return redirect()->route('invoices.show', $invoice)
        ->with('error', 'Esta factura ya fue validada por la DIAN...');
}
```

#### `send(Request $request, Invoice $invoice): RedirectResponse`
**Este es el método que ENVÍA la factura a la DIAN** (vía Factus).
Verifica permisos, llama al servicio `FactusService::validateInvoice()` y
redirige con feedback:

```php
public function send(Request $request, Invoice $invoice): RedirectResponse
{
    $this->authorize('send', $invoice);  // InvoicePolicy::send

    $result = $this->factusService->validateInvoice($invoice);

    if ($result['success'] ?? false) {
        $cufe = $result['cufe'] ?? null;
        $message = $cufe
            ? 'Factura validada exitosamente. CUFE: ' . substr($cufe, 0, 20) . '...'
            : 'Factura validada exitosamente';
        return redirect()->route('invoices.show', $invoice)->with('success', $message);
    }

    // Si falló, mostrar errores detallados
    $errors = $result['errors'] ?? [];
    $errorMessage = $result['message'] ?? 'Error al validar la factura';
    // ... formatea errores y redirige
}
```

#### `cancel(Request $request, Invoice $invoice): RedirectResponse`
Llama a `$factusService->deleteInvoice($invoice)`. Solo funciona si la factura
**no fue notificada al cliente** (la DIAN no permite borrar documentos
entregados). Si está bloqueada, sugiere crear una Nota Crédito.

#### `refreshStatus(Request $request, Invoice $invoice): RedirectResponse`
Consulta el estado actual en Factus y lo guarda:

```php
$status = $this->factusService->checkInvoiceStatus($invoice);
if (!isset($status['error'])) {
    $invoice->update(['factus_response' => $status]);
}
```

#### `downloadFactusPdf(Request $request, Invoice $invoice)`
Descarga el PDF **firmado por la DIAN** desde Factus. Si falla, hace fallback
al PDF local generado con DomPDF:

```php
$pdfContent = $this->factusService->downloadInvoicePdf($invoice);

if ($pdfContent === null) {
    return $this->pdfService->stream($invoice);  // fallback PDF local
}

return response($pdfContent, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="factura-' . $invoice->number . '.pdf"',
]);
```

#### `downloadFactusXml(Request $request, Invoice $invoice)`
Descarga el XML firmado de Factus con la respuesta `application/xml`.

---

## 4. Form Request: `StoreInvoiceRequest`

Ubicación: `app/Http/Requests/Invoice/StoreInvoiceRequest.php`

Valida el formulario del frontend con reglas estrictas multi-tenant:

```php
public function authorize(): bool
{
    return auth()->check() && auth()->user()->tenant_id !== null;
}

public function rules(): array
{
    $tenantId = auth()->user()?->tenant_id;
    return [
        'client_id' => [
            'required',
            Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
        ],
        'issue_date' => 'nullable|date',
        'due_date' => 'nullable|date|after_or_equal:issue_date',
        'notes' => 'nullable|string|max:1000',
        'items' => 'required|array|min:1',
        'items.*.product_id' => [
            'nullable',
            Rule::exists('products', 'id')->where('tenant_id', $tenantId),
        ],
        'items.*.description' => 'required|string|max:255',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.tax' => 'nullable|numeric|min:0|max:100',
    ];
}
```

Nótese el uso de `Rule::exists(...)->where('tenant_id', $tenantId)` para
garantizar que el cliente y los productos seleccionados pertenezcan al
mismo tenant.

---

## 5. Vistas Blade (`resources/views/invoice/`)

### `index.blade.php`
Listado paginado de facturas con:
- Tarjetas de estadísticas (total, borradores, pendientes, enviadas, monto).
- Filtros: búsqueda por número y filtro por estado.
- Tabla con: número, cliente, fecha, total, estado (badge) y acciones.
- Botón "Nueva Factura" en la parte superior derecha.

### `create.blade.php`
Formulario de creación. Características:
- **Selector de cliente** poblado desde `$clients`.
- **Fechas**: emisión (default hoy) y vencimiento.
- **Notas** (textarea, máx 1000 chars).
- **Ítems dinámicos**: un `<template id="itemRowTemplate">` define la fila
  base; JavaScript escucha el botón "Agregar ítem" y clona el template
  sustituyendo el placeholder `__INDEX__` en los `name`.
- **Autocompletado desde producto**: al seleccionar un producto del `<select>`,
  JavaScript lee `$productsForJs` (pasado con `@json`) y rellena descripción,
  precio unitario e IVA.
- **Cálculo en vivo**: subtotal, impuestos y total se recalculan en
  `calculateTotals()` cada vez que cambia cantidad, precio o IVA.
- **Dos botones de submit**:
  - `name="action" value="draft"` → Guarda como borrador.
  - `name="action" value="pending"` → Crea y marca como pendiente (luego se
    enviará a Factus en otro paso).

El formulario envía a `POST /invoices` con CSRF.

### `edit.blade.php`
Igual que `create` pero con datos precargados y `$invoice->items` cargado.

### `show.blade.php`
Vista detalle con mucho contexto:
- **Alertas** de éxito/error (incluyendo errores detallados de Factus en
  `session('factus_errors')`).
- **Encabezado**: número, `reference_code` (si existe).
- **Botones contextuales** según el estado:
  - **Sin `reference_code`**: "Validar en Factus", "Editar", "Eliminar local".
  - **Con `reference_code`**: "Crear Nota Crédito", "Actualizar estado",
    "PDF DIAN" (link público), "PDF local", "XML", "QR DIAN",
    "Eliminar de Factus (pre-cliente)".
- **Panel verde** con el CUFE cuando la factura ya fue validada (incluye
  `validated_at` y advertencia de inmutabilidad).
- **Datos del cliente** e información de la factura.
- **Tabla de ítems** con subtotales.
- **Historial de `IntegrationLog`**: muestra cada llamada a la API de Factus
  con fecha, acción, estado (éxito/error/pendiente) y mensaje de error.
- **Sección educativa** "Cómo funciona el flujo de facturación electrónica"
  con 3 pasos visuales.
- **Modales** de Alpine.js para confirmación de eliminación local y de Factus.

### `_item_row.blade.php`
Partial reutilizado para una fila de ítem (usado tanto en `create` como en
`edit`).

---

## 6. Servicio: `InvoiceService`

Ubicación: `app/Services/InvoiceService.php`

CRUD puro contra la base de datos local. No toca Factus en ningún momento.

### `create(StoreInvoiceRequest $request): Invoice`
Punto central de la creación local:

```php
public function create(StoreInvoiceRequest $request): Invoice
{
    return DB::transaction(function () use ($request) {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['issue_date'] = $data['issue_date'] ?? now()->toDateString();

        $invoice = Invoice::create($data);
        $invoice->generateNumber();  // Genera INV-2026-000007

        foreach ($data['items'] as $itemData) {
            $itemData['invoice_id'] = $invoice->id;
            $itemData['subtotal'] = $itemData['quantity'] * $itemData['unit_price'];
            $itemData['tax'] = $itemData['tax'] ?? 0;
            InvoiceItem::create($itemData);
        }

        $invoice->calculateTotals();  // Suma subtotal, tax_total, total

        return $invoice->load('items', 'client');
    });
}
```

### `update()` / `delete()` / `changeStatus()` / `getInvoicesByStatus()`
CRUD estándar con `DB::transaction` para atomicidad.

### `getInvoiceStats(): array`
Consulta agregada que devuelve totales por estado:

```php
return [
    'total' => ...,
    'draft' => ...,
    'pending' => ...,
    'sent' => ...,
    'total_amount' => ...,
];
```

---

## 7. Servicio: `FactusService` (corazón de la integración)

Ubicación: `app/Services/FactusService.php`

Lee credenciales desde `config/services.php` (cargadas del `.env`):

```php
// config/services.php
'factus' => [
    'base_url'       => env('FACTUS_BASE_URL'),
    'username'       => env('FACTUS_USERNAME'),
    'password'       => env('FACTUS_PASSWORD'),
    'client_id'      => env('FACTUS_CLIENT_ID'),
    'client_secret'  => env('FACTUS_CLIENT_SECRET'),
    'timeout'        => (int) env('FACTUS_TIMEOUT', 30),
    'retry'          => (int) env('FACTUS_RETRY', 3),
    'connect_timeout'=> (int) env('FACTUS_CONNECT_TIMEOUT', 5),
],
```

### 7.1. Autenticación OAuth2

#### `isConfigured(): bool`
Verifica que todas las credenciales estén presentes.

#### `authenticate(): array`
`POST {baseUrl}/oauth/token` con `grant_type=password`:

```php
$response = $this->http()
    ->asForm()
    ->post($this->baseUrl . '/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => $this->clientId,
        'client_secret' => $this->clientSecret,
        'username'      => $this->username,
        'password'      => $this->password,
    ]);
return $response->json() ?? [];
```

#### `refreshToken(string $refreshToken): array`
`POST /oauth/token` con `grant_type=refresh_token`.

#### `getToken(): ?string`
Devuelve un access_token cacheado por **3500 segundos** (≈ 58 min). Si no
existe, llama a `authenticate()`. Si la autenticación inicial no devuelve
token, intenta con el `refresh_token` cacheado:

```php
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
    return null;
});
```

#### `authed(): PendingRequest`
Devuelve un cliente HTTP preconfigurado con `withToken($token)` y
`asJson()`/`acceptJson()`. Si el token es `null`, lanza
`RuntimeException('No se pudo autenticar con Factus...')`.

#### `http(): PendingRequest`
Cliente HTTP base con:
- `timeout($this->timeout)` — timeout total de 30s por defecto.
- `connectTimeout($this->connectTimeout)` — 5s para conectar.
- `retry($this->retry, ...)` — 3 reintentos con backoff `[100, 500, 1500]ms`.
- `acceptJson()`.

### 7.2. Construcción del payload (`buildInvoicePayload`)

**Este es el método que arma el JSON exacto que se envía a Factus**:

```php
public function buildInvoicePayload(Invoice $invoice): array
{
    $invoice->loadMissing('client', 'items.product', 'tenant');

    $client = $invoice->client;
    $isCompany = $client && $client->document
        && Str::of($client->document)->upper()->startsWith(['NIT']);
    $documentCode = $isCompany ? '31' : '13';  // 31=NIT, 13=Cédula

    $nitParts = $isCompany ? $this->splitNit($client->document) : null;

    $payload = [
        'reference_code' => $invoice->reference_code
            ?: ('FACT-' . $invoice->tenant_id . '-' . $invoice->id . '-' . time()),
        'document' => '01',                  // 01 = Factura de venta
        'operation_type' => '10',            // 10 = Estándar
        'send_email' => true,                // Factus envía email al cliente

        'customer' => [
            'identification_document_code' => $documentCode,
            'identification' => $client
                ? ($nitParts['number'] ?? $client->document ?? '222222222222')
                : '222222222222',
            'legal_organization_code' => $isCompany ? '1' : '2',  // 1=Jurídica, 2=Natural
            'tribute_code' => '01',          // 01 = IVA
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
                    'code' => '01',           // 01 = IVA
                    'rate' => number_format($rate, 2, '.', ''),
                ];
            }
            return [
                'code_reference'    => (string) ($item->product_id ?? $item->id),
                'name'              => $item->description,
                'quantity'          => number_format((float) $item->quantity, 2, '.', ''),
                'discount_rate'     => '0.00',
                'price'             => number_format((float) $item->unit_price, 2, '.', ''),
                'unit_measure_code' => '94',   // 94 = Unidad
                'standard_code'     => '999', // 999 = Estándar de producto
                'taxes'             => $taxes,
            ];
        })->values()->all(),
    ];

    // Payment details (forma y método de pago)
    if ($invoice->due_date) {
        $payload['payment_details'] = [[
            'payment_form'        => '2',   // 2 = Crédito
            'payment_method_code' => '42',  // 42 = Transferencia
            'amount'              => number_format((float) $invoice->total, 2, '.', ''),
            'due_date'            => $invoice->due_date->toDateString(),
        ]];
    } else {
        $payload['payment_details'] = [[
            'payment_form'        => '1',   // 1 = Contado
            'payment_method_code' => '42',
            'amount'              => number_format((float) $invoice->total, 2, '.', ''),
        ]];
    }

    // Si es NIT, agregar el dígito de verificación
    if ($nitParts) {
        $payload['customer']['dv'] = $nitParts['dv'];
    }

    // Observaciones (truncadas a 250 chars)
    if ($invoice->notes) {
        $payload['observation'] = Str::limit($invoice->notes, 250, '');
    }

    return $payload;
}
```

#### Helper: `splitNit(?string $nit): ?array`
Divide un NIT colombiano en `number` y `dv` (dígito de verificación):

```php
$clean = preg_replace('/[^0-9\-]/', '', $nit);
if (str_contains($clean, '-')) {
    [$number, $dv] = explode('-', $clean, 2);
    return ['number' => $number, 'dv' => $dv];
}
return ['number' => $clean, 'dv' => null];
```

#### Ejemplo de payload generado

```json
{
  "reference_code": "FACT-1-42-1719161234",
  "document": "01",
  "operation_type": "10",
  "send_email": true,
  "customer": {
    "identification_document_code": "13",
    "identification": "1234567890",
    "legal_organization_code": "2",
    "tribute_code": "01",
    "names": "Juan Pérez",
    "address": "Calle 123 #45-67",
    "email": "juan@example.com",
    "phone": "3001234567"
  },
  "items": [
    {
      "code_reference": "5",
      "name": "Servicio de consultoría",
      "quantity": "1.00",
      "discount_rate": "0.00",
      "price": "500000.00",
      "unit_measure_code": "94",
      "standard_code": "999",
      "taxes": [
        { "code": "01", "rate": "19.00" }
      ]
    }
  ],
  "payment_details": [
    {
      "payment_form": "1",
      "payment_method_code": "42",
      "amount": "595000.00"
    }
  ]
}
```

### 7.3. Validación y envío a la DIAN (`validateInvoice`)

**El método que efectivamente "envía" la factura a la DIAN**:

```php
public function validateInvoice(Invoice $invoice): array
{
    if (!$this->isConfigured()) {
        return [
            'success' => false,
            'error' => 'config_missing',
            'message' => 'Factus no está configurado. Agrega las credenciales en .env',
        ];
    }

    // 1) Crear log de auditoría (pendiente)
    $log = IntegrationLog::create([
        'tenant_id'   => $invoice->tenant_id,
        'invoice_id'  => $invoice->id,
        'provider'    => 'factus',
        'action'      => 'validate_invoice',
        'status'      => 'pending',
        'executed_at' => now(),
    ]);

    try {
        // 2) Construir payload y guardar reference_code
        $payload = $this->buildInvoicePayload($invoice);
        $invoice->update(['reference_code' => $payload['reference_code']]);
        $log->update(['request_payload' => $payload]);

        // 3) Llamada HTTP a la API de Factus
        $response = $this->authed()
            ->post($this->baseUrl . '/v2/bills/validate', $payload);
        $responseData = $response->json() ?? [];

        // 4) Si la respuesta HTTP no fue exitosa
        if ($response->failed()) {
            $message = $responseData['message'] ?? 'HTTP ' . $response->status();
            $errors  = $responseData['errors'] ?? [];

            $log->update([
                'response_payload' => $responseData,
                'status'           => 'error',
                'error_message'    => is_array($errors) ? json_encode($errors) : $message,
            ]);

            return [
                'success' => false,
                'status'  => $response->status(),
                'message' => $message,
                'errors'  => $errors,
                'response'=> $responseData,
            ];
        }

        // 5) Respuesta exitosa: extraer datos y actualizar factura
        $data = $responseData['data'] ?? $responseData;
        $bill = $data['bill'] ?? $data;
        $cufe        = $bill['cufe'] ?? $data['cufe'] ?? null;
        $links       = $bill['links'] ?? $data['links'] ?? [];
        $qr          = $links['qr'] ?? $bill['qr'] ?? $bill['qr_link'] ?? null;
        $publicUrl   = $links['public_url'] ?? $bill['public_url'] ?? $data['public_url'] ?? null;
        $status      = $bill['status'] ?? $data['status'] ?? 'VALIDATED';
        $numberingId = $bill['numbering_range']['id'] ?? $data['numbering_range']['id'] ?? null;

        $invoice->update([
            'status'          => 'sent',
            'is_validated'    => true,
            'cufe'            => $cufe,
            'qr_link'         => $qr,
            'status_factus'   => $status,
            'external_id'     => (string) ($numberingId ?? $bill['id'] ?? $data['id'] ?? $invoice->id),
            'validated_at'    => now(),
            'factus_response' => $responseData,  // Cast a array
        ]);

        $log->update([
            'response_payload' => $responseData,
            'status'           => 'success',
        ]);

        return [
            'success'        => true,
            'cufe'           => $cufe,
            'qr'             => $qr,
            'status'         => $status,
            'public_url'     => $publicUrl,
            'bill'           => $bill,
            'reference_code' => $payload['reference_code'],
        ];
    } catch (\Throwable $e) {
        $log->update([
            'status'        => 'error',
            'error_message' => $e->getMessage(),
        ]);

        Log::error('FactusService validateInvoice Error: ' . $e->getMessage(), [
            'invoice_id' => $invoice->id,
            'trace'      => $e->getTraceAsString(),
        ]);

        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

**Endpoint llamado**: `POST {FACTUS_BASE_URL}/v2/bills/validate`

**Efecto sobre la BD local**:
- `invoices.reference_code` ← `FACT-{tenant_id}-{invoice_id}-{timestamp}`
- `invoices.status` ← `'sent'`
- `invoices.is_validated` ← `true`
- `invoices.cufe` ← CUFE devuelto por Factus
- `invoices.qr_link` ← link al QR
- `invoices.status_factus` ← estado devuelto por Factus
- `invoices.external_id` ← ID externo de Factus
- `invoices.validated_at` ← timestamp
- `invoices.factus_response` ← JSON completo de la respuesta (cast a array)
- `integration_logs` ← registro con request, response y estado

### 7.4. Métodos auxiliares de FactusService

#### `sendInvoice(Invoice $invoice): array`
Alias de `validateInvoice()` — solo conserva compatibilidad semántica.

#### `dispatchSendInvoice(Invoice $invoice): void`
Despacha un `SendInvoiceJob` a la cola `invoices` para procesamiento
asíncrono (ver sección 8).

#### `checkInvoiceStatus(Invoice $invoice): array`
`GET {baseUrl}/v1/bills/show/{reference_code}` — consulta el estado actual
en Factus. Devuelve la respuesta cruda o `['error' => '...']` si falla.

#### `downloadInvoicePdf(Invoice $invoice): ?string`
Intenta descargar el PDF firmado por la DIAN. Orden de intento:
1. El `public_url` guardado en `factus_response.data.links.public_url`.
2. `{baseUrl}/v2/bills/download-pdf/{reference_code}`.
3. `{baseUrl}/v1/bills/download-pdf/{reference_code}`.

Si ninguno devuelve un PDF válido, retorna `null` y el controlador hace
fallback al PDF local.

#### `downloadInvoiceXml(Invoice $invoice): ?string`
Intenta descargar el XML firmado:
1. `{baseUrl}/v2/bills/download-xml/{reference_code}`.
2. `{baseUrl}/v1/bills/download-xml/{reference_code}`.

#### `deleteInvoice(Invoice $invoice, bool $force = false): array`
`DELETE {baseUrl}/v1/bills/{reference_code}`. **Validación previa** con
`wasNotifiedToClient()`: si la factura ya fue notificada al cliente y
`$force` es `false`, devuelve `blocked=true` con instrucción de crear
Nota Crédito. Si la eliminación es exitosa, marca la factura con
`status=cancelled` y `status_factus=DELETED`.

#### `wasNotifiedToClient(Invoice $invoice): bool`
Verifica si la factura fue entregada al cliente. Lee `status_factus` o el
campo `status` dentro de `factus_response.data`. Retorna `true` si es
`NOTIFIED` o `DELIVERED`.

#### `listNumberingRanges(): array`
`GET {baseUrl}/v1/numbering-ranges` — lista los rangos de numeración
disponibles.

#### `testConnection(): array`
Prueba la conexión intentando autenticar y devuelve el resultado.

#### `buildCreditNotePayload(CreditNote $creditNote): array`
Construye el payload para Notas Crédito (similar a `buildInvoicePayload`
pero con `correction_concept_code`, `billing_reference` apuntando al CUFE
de la factura original, `discrepancies`, etc.).

---

## 8. Job asíncrono: `SendInvoiceJob`

Ubicación: `app/Jobs/SendInvoiceJob.php`

Implementa `ShouldQueue` (debe ejecutarse en una cola) y usa los traits
`Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`.

### Definición

```php
class SendInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Invoice $invoice
    ) {
        $this->onQueue('invoices');
    }
    // ...
}
```

### Configuración

- **Cola**: `invoices` (se encola en este canal específico).
- **Intentos**: hasta 3 (`$tries = 3`).
- **Backoff (reintentos)**: 60 segundos por defecto, pero el método
  `backoff()` devuelve un array personalizado: `[60, 300, 900]` segundos
  (1 min, 5 min, 15 min) entre reintentos.
- **Serialización**: el modelo `Invoice` se serializa para el job
  (SerializesModels).

### Método `handle(FactusService $factusService): void`

Lógica principal del job:

```php
public function handle(FactusService $factusService): void
{
    // 1) Si ya fue validada, no hacer nada
    if ($this->invoice->reference_code) {
        Log::info('SendInvoiceJob: Invoice ' . $this->invoice->number . ' already validated, skipping.');
        return;
    }

    // 2) Si el estado no permite envío, omitir
    if (!in_array($this->invoice->status, ['draft', 'pending'], true)) {
        Log::info('SendInvoiceJob: Invoice ' . $this->invoice->number
            . ' in status ' . $this->invoice->status . ', skipping.');
        return;
    }

    Log::info('SendInvoiceJob: Validating invoice ' . $this->invoice->number . ' in Factus');

    try {
        // 3) Llamar al servicio que envía a Factus/DIAN
        $result = $factusService->validateInvoice($this->invoice);

        if ($result['success'] ?? false) {
            Log::info('SendInvoiceJob: Invoice ' . $this->invoice->number
                . ' validated. CUFE: ' . ($result['cufe'] ?? 'N/A'));
        } else {
            Log::warning('SendInvoiceJob: Invoice ' . $this->invoice->number
                . ' failed: ' . ($result['message'] ?? 'Unknown error'));
        }
    } catch (\Throwable $e) {
        Log::error('SendInvoiceJob: Invoice ' . $this->invoice->number
            . ' exception: ' . $e->getMessage());
        throw $e;  // Relanza para que el worker aplique el backoff/retry
    }
}
```

### Método `backoff(): array`

```php
public function backoff(): array
{
    return [60, 300, 900];  // 1 min, 5 min, 15 min
}
```

Define el tiempo de espera entre reintentos cuando una ejecución falla.

### Método `failed(\Throwable $exception): void`

Se ejecuta cuando el job falla permanentemente tras agotar los 3 intentos:

```php
public function failed(\Throwable $exception): void
{
    Log::error('SendInvoiceJob: Invoice ' . $this->invoice->number . ' permanently failed', [
        'exception' => $exception->getMessage(),
        'trace'     => $exception->getTraceAsString(),
    ]);

    $this->invoice->update(['status' => 'error']);
}
```

Marca la factura con `status='error'` para que sea visible como fallida.

### Despachar el job

El job se despacha desde `FactusService::dispatchSendInvoice()`:

```php
public function dispatchSendInvoice(Invoice $invoice): void
{
    SendInvoiceJob::dispatch($invoice);
}
```

### Estado actual de uso

**Importante**: aunque el job existe y está correctamente implementado, en
el flujo web actual (controlador `InvoiceController@send`) **no se está
usando**. La llamada a Factus se hace de forma **síncrona** en el request
HTTP:

```php
// InvoiceController@send
$result = $this->factusService->validateInvoice($invoice);
```

El job está disponible para escenarios donde se quiera desacoplar el envío
(por ejemplo, un cron que reintente facturas fallidas, o un endpoint
interno que encole envíos masivos). Para activarlo, bastaría con
reemplazar la llamada síncrona por:

```php
$this->factusService->dispatchSendInvoice($invoice);
```

---

## 9. Modelo: `Invoice`

Ubicación: `app/Models/Invoice.php`

### Atributos fillable

```php
protected $fillable = [
    'tenant_id', 'client_id', 'number', 'reference_code',
    'issue_date', 'due_date', 'status', 'status_factus',
    'is_validated', 'subtotal', 'tax_total', 'total', 'notes',
    'external_id', 'cufe', 'qr_link', 'factus_response', 'validated_at',
];
```

### Casts

```php
protected function casts(): array
{
    return [
        'issue_date'      => 'date',
        'due_date'        => 'date',
        'subtotal'        => 'decimal:2',
        'tax_total'       => 'decimal:2',
        'total'           => 'decimal:2',
        'is_validated'    => 'boolean',
        'factus_response' => 'array',  // JSON ↔ array
        'validated_at'    => 'datetime',
    ];
}
```

`factus_response` se almacena como JSON y se deserializa automáticamente
a array PHP al leerlo.

### TenantScope

```php
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope);
}
```

Filtra **todas** las consultas de `Invoice` por el `tenant_id` del usuario
autenticado, evitando fugas de datos entre tenants.

### Relaciones

- `tenant()` — `BelongsTo Tenant`
- `client()` — `BelongsTo Client`
- `items()` — `HasMany InvoiceItem`
- `integrationLogs()` — `HasMany IntegrationLog`

### Scopes

`draft()`, `pending()`, `sent()`, `validated()`, `pendingValidation()` —
filtros de Eloquent reutilizables.

### `calculateTotals(): void`

Recalcula y guarda los totales a partir de los ítems:

```php
public function calculateTotals(): void
{
    $this->subtotal  = $this->items->sum('subtotal');
    $this->tax_total = $this->items->sum(fn($item) => $item->subtotal * $item->tax / 100);
    $this->total     = $this->subtotal + $this->tax_total;
    $this->save();
}
```

### `generateNumber(): string`

Genera el número correlativo de factura con prefijo del tenant:

```php
public function generateNumber(): string
{
    return \DB::transaction(function () {
        $tenant = \App\Models\Tenant::lockForUpdate()->findOrFail($this->tenant_id);

        $prefix = $tenant->invoice_prefix ?: 'INV';
        $year   = date('Y');
        $count  = $tenant->invoices()
            ->whereYear('created_at', $year)
            ->count() + 1;

        $this->number = sprintf('%s-%s-%06d', $prefix, $year, $count);
        $this->save();

        return $this->number;
    });
}
```

Usa `lockForUpdate` para garantizar atomicidad en la numeración
(importante bajo concurrencia).

---

## 10. Policy: `InvoicePolicy`

Ubicación: `app/Policies/InvoicePolicy.php`

| Método       | Reglas                                                                                          |
|--------------|-------------------------------------------------------------------------------------------------|
| `viewAny`    | Siempre `true` (el scope global filtra por tenant).                                            |
| `view`       | Solo si el `tenant_id` del usuario coincide con el de la factura.                               |
| `create`     | Siempre `true`.                                                                                 |
| `update`     | Mismo tenant, **no validado** por la DIAN, y no `approved`/`cancelled`.                        |
| `delete`     | Mismo tenant, **no validado**, y solo si `status='draft'`.                                     |
| `send`       | Mismo tenant, no `cancelled`, y en `draft`/`pending`/`sent` (o `sent` sin reference_code).     |
| `cancel`     | Mismo tenant, debe tener `reference_code` o `is_validated`, y no estar `cancelled`.             |

**Consecuencia clave**: una factura validada por la DIAN es **inmutable** —
no se puede editar, no se puede eliminar localmente, y solo se puede
"cancelar" (eliminar de Factus) si no fue notificada al cliente.

---

## 11. Modelo: `IntegrationLog`

Ubicación: `app/Models/IntegrationLog.php`

Registra **cada llamada** a la API de Factus, sirviendo como auditoría:

- `tenant_id`, `invoice_id` — Relaciones.
- `provider` — `'factus'`.
- `action` — `'validate_invoice'`, etc.
- `request_payload`, `response_payload` — JSON completos (cast a array).
- `status` — `'pending'` / `'success'` / `'error'`.
- `error_message` — Mensaje de error en caso de fallo.
- `executed_at` — Timestamp de la llamada.

Tiene scopes `success()`, `error()`, `pending()`.

La vista `invoice.show` muestra el historial completo al final de la página.

---

## 12. Middleware: `TenantMiddleware`

Ubicación: `app/Http/Middleware/TenantMiddleware.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user();
    if (!$user->tenant_id) {
        abort(403, 'No tienes una empresa asociada.');
    }
    if (!$user->tenant->is_active) {
        abort(403, 'Tu empresa está inactiva.');
    }
    app()->instance('current_tenant_id', $user->tenant_id);
    return $next($request);
}
```

Garantiza que cada request autenticado tenga un tenant válido y activo,
e inyecta el `current_tenant_id` en el container para uso por servicios.

---

## 13. Servicios auxiliares

### `ClientService`
- `create()`, `update()`, `delete()` — CRUD con `Cache::forget`.
- `getActiveClients()` — Lista cacheada por tenant (`tenant:{id}:active_clients`, TTL 3600s).

### `ProductService`
- Análogo a `ClientService` para productos.
- `getActiveProducts()` — Cacheado por tenant.
- `getProductsByType(string $type)` — Filtra por tipo (`product` o `service`).

### `InvoicePdfService`
- `generate(Invoice $invoice)` — Genera PDF con DomPDF (paquete
  `barryvdh/laravel-dompdf`) usando la vista `resources/views/pdf/invoice.blade.php`.
  Lo devuelve como `Response` con `Content-Disposition: attachment`.
- `stream(Invoice $invoice)` — Igual pero `Content-Disposition: inline`
  (visualización en navegador).

---

## 14. Flujo end-to-end paso a paso

1. **GET `/invoices/create`** → `InvoiceController@create` carga clientes y
   productos desde `ClientService` / `ProductService`, renderiza
   `invoice.create.blade.php`.

2. **Usuario llena el form** y presiona "Crear y Enviar" → submit a
   `POST /invoices` con `action=pending` (o `draft`).

3. **`StoreInvoiceRequest`** valida:
   - `client_id` existe en el tenant actual.
   - `due_date >= issue_date`.
   - Al menos 1 ítem.
   - Cada ítem: descripción, cantidad, precio válidos, IVA entre 0 y 100.

4. **`InvoiceController@store`** → **`InvoiceService::create($request)`**:
   - `DB::transaction`:
     - Crea `Invoice` con `status='pending'` o `'draft'`.
     - `$invoice->generateNumber()` → lockea tenant → genera
       `INV-2026-000007`.
     - Crea `InvoiceItem[]` con `subtotal = qty * price`.
     - `$invoice->calculateTotals()` → guarda `subtotal`, `tax_total`, `total`.
   - Devuelve la factura con `items` y `client` cargados.

5. **Redirect** a `invoices.index` con flash "Factura creada correctamente".

6. **Usuario abre `GET /invoices/{id}`** → `InvoiceController@show` carga
   `client`, `items.product`, `integrationLogs` → renderiza
   `invoice.show.blade.php`.

7. **Usuario presiona "Validar en Factus"** → `POST /invoices/{id}/send`.

8. **`InvoiceController@send`**:
   - `authorize('send', $invoice)` → `InvoicePolicy::send`.
   - **`FactusService::validateInvoice($invoice)`**:
     - Verifica configuración.
     - Crea `IntegrationLog` (status=pending).
     - `buildInvoicePayload()` → arma el JSON (ver sección 7.2).
     - `getToken()` → OAuth2 con cache (o refresh).
     - `POST {baseUrl}/v2/bills/validate` con `withToken` y el payload.
     - **Éxito**: actualiza `Invoice` con `status=sent`, `is_validated=true`,
       `cufe`, `qr_link`, `status_factus`, `external_id`, `validated_at`,
       `factus_response`.
     - Marca `IntegrationLog` como `success` con la respuesta.
   - **Redirect** a `invoices.show` con flash mostrando el CUFE.

9. **Usuario ve el panel verde** con el CUFE y los nuevos botones
   "PDF DIAN", "XML", "QR DIAN", "Actualizar estado",
   "Crear Nota Crédito", "Eliminar de Factus (pre-cliente)".

10. **Si necesita el PDF firmado** → `GET /invoices/{id}/factus-pdf` →
    `FactusService::downloadInvoicePdf()`:
    - Intenta `public_url` (link público directo de Factus/DIAN).
    - Si falla, intenta `/v2/bills/download-pdf/{ref}`.
    - Si falla, intenta `/v1/bills/download-pdf/{ref}`.
    - Si todo falla, `null` → fallback a `InvoicePdfService::stream()` (PDF
      local con DomPDF).

11. **Si quiere refrescar el estado** → `POST /invoices/{id}/refresh-status`
    → `FactusService::checkInvoiceStatus()` → guarda la respuesta cruda en
    `factus_response`.

12. **Si la factura no se entregó al cliente y hay error** →
    `POST /invoices/{id}/cancel` → `FactusService::deleteInvoice()` →
    valida `wasNotifiedToClient()` → `DELETE /v1/bills/{ref}` → marca
    `status=cancelled`, `status_factus=DELETED`.

13. **Si la factura ya se entregó** → el botón "Eliminar de Factus" está
    visible pero al presionarlo Factus devuelve un error y la UI muestra:
    *"La factura ya fue entregada al cliente. La DIAN no permite
    eliminarla. Debes crear una NOTA CRÉDITO."*

---

## 15. Diagrama de dependencias

```
                        ┌────────────────────────────────────────┐
                        │            Rutas (web.php)             │
                        │  auth + tenant + resource:invoices    │
                        └──────────────┬─────────────────────────┘
                                       │
                                       ▼
                        ┌────────────────────────────────────────┐
                        │   InvoiceController                    │
                        │  index, create, store, show, edit,     │
                        │  update, destroy, send, cancel,         │
                        │  refreshStatus, downloadFactusPdf,      │
                        │  downloadFactusXml, downloadPdf,        │
                        │  streamPdf                              │
                        └──┬─────────┬────────┬─────────┬─────────┘
                           │         │        │         │
            ┌──────────────┘         │        │         └──────────────┐
            ▼                        ▼        ▼                        ▼
   ┌─────────────────┐    ┌──────────────────┐  ┌──────────────────┐
   │ InvoiceService  │    │ ClientService    │  │ ProductService   │
   │  CRUD local     │    │ + cache          │  │ + cache          │
   └────────┬────────┘    └──────────────────┘  └──────────────────┘
            │
            ▼
   ┌─────────────────────────────────────┐
   │  Invoice, InvoiceItem, Tenant       │
   │  (Eloquent + TenantScope + DB)      │
   └─────────────────────────────────────┘

            ┌─────────────────────┐
            │   FactusService     │ ── Http::retry() ──▶ API Factus ──▶ DIAN
            │  OAuth2 + payload   │             │
            │  + validateInvoice  │             ▼
            │  + checkStatus      │     IntegrationLog (auditoría)
            │  + downloadPdf/Xml  │             │
            │  + deleteInvoice    │             ▼
            │  + dispatchJob      │     SendInvoiceJob (cola 'invoices')
            └─────────────────────┘

            ┌─────────────────────┐
            │  InvoicePdfService  │ ── DomPDF ──▶ pdf/invoice.blade.php
            └─────────────────────┘
```

---

## 16. Resumen de archivos clave

| Capa             | Archivo                                                       |
|------------------|---------------------------------------------------------------|
| Rutas            | `routes/web.php:16-46`                                        |
| Controlador      | `app/Http/Controllers/Invoice/InvoiceController.php`         |
| Form Request     | `app/Http/Requests/Invoice/StoreInvoiceRequest.php`           |
| Servicio local   | `app/Services/InvoiceService.php`                             |
| Servicio Factus  | `app/Services/FactusService.php`                              |
| Job asíncrono    | `app/Jobs/SendInvoiceJob.php`                                 |
| Modelo Invoice   | `app/Models/Invoice.php`                                      |
| Modelo log       | `app/Models/IntegrationLog.php`                               |
| Policy           | `app/Policies/InvoicePolicy.php`                              |
| Middleware       | `app/Http/Middleware/TenantMiddleware.php`                    |
| Servicio PDF     | `app/Services/InvoicePdfService.php`                          |
| Vista listado    | `resources/views/invoice/index.blade.php`                     |
| Vista crear      | `resources/views/invoice/create.blade.php`                    |
| Vista detalle    | `resources/views/invoice/show.blade.php`                      |
| Vista editar     | `resources/views/invoice/edit.blade.php`                      |
| Partial ítem     | `resources/views/invoice/_item_row.blade.php`                 |
| Vista PDF        | `resources/views/pdf/invoice.blade.php`                       |
| Config Factus    | `config/services.php:38-47`                                   |
