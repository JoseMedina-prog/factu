# Guía de Integración con Factus API v2

Documentación completa de cómo funciona el envío de facturas electrónicas en este proyecto, basada en la API oficial de [Factus](https://developers.factus.com.co/).

---

## Índice

1. [Arquitectura general](#arquitectura-general)
2. [Autenticación OAuth2](#autenticación-oauth2)
3. [Flujo completo de envío](#flujo-completo-de-envío)
4. [Estructura del JSON de envío](#estructura-del-json-de-envío)
5. [Estructura del JSON de respuesta](#estructura-del-json-de-respuesta)
6. [Códigos importantes](#códigos-importantes)
7. [Estados de una factura](#estados-de-una-factura)
8. [Tablas de referencia](#tablas-de-referencia)
9. [Ejemplo end-to-end](#ejemplo-end-to-end)
10. [Acciones disponibles](#acciones-disponibles)
11. [Errores comunes](#errores-comunes)

---

## Arquitectura general

```
┌─────────────────────────────────────────────────────────────────┐
│                    USUARIO EN LA APP                            │
└────────────────────────┬────────────────────────────────────────┘
                         │ Click "Validar en Factus"
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  routes/web.php → POST /invoices/{invoice}/send                 │
└────────────────────────┬────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  InvoiceController::send()                                      │
│  - authorize('send', $invoice)                                  │
│  - FactusService::validateInvoice($invoice)                     │
└────────────────────────┬────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  FactusService::validateInvoice($invoice)                       │
│  1. Crea IntegrationLog                                          │
│  2. Construye payload desde el modelo                           │
│  3. Solicita token OAuth2 (cache 3500s)                         │
│  4. POST https://api-sandbox.factus.com.co/v2/bills/validate    │
│  5. Guarda CUFE, QR, status en la factura                       │
│  6. Actualiza IntegrationLog con response                       │
└────────────────────────┬────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│              FACTUS API (sandbox o producción)                  │
│  - Valida estructura del JSON                                   │
│  - Genera CUFE (Código Único de Facturación Electrónica)        │
│  - Envía el documento a la DIAN                                 │
│  - Devuelve respuesta con CUFE, QR y public_url                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Autenticación OAuth2

Factus usa OAuth2 con el flujo **Resource Owner Password Credentials**.

### Endpoint

```
POST https://api-sandbox.factus.com.co/oauth/token
```

### Formato de la petición (form-data)

| Campo | Valor | Descripción |
|-------|-------|-------------|
| `grant_type` | `password` | Tipo de autenticación |
| `client_id` | `a215b2f4-...` | Identificador del cliente |
| `client_secret` | `Wvq0w4zc...` | Secreto del cliente |
| `username` | `sandboxv2@factus.com.co` | Correo del usuario |
| `password` | `sandbox2026%` | Contraseña del usuario |

### Respuesta exitosa (200)

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def502004f8b..."
}
```

### Implementación

El token se cachea automáticamente por **3500 segundos** (≈58 minutos) usando el sistema de caché de Laravel. Si el token expira, se usa automáticamente el `refresh_token` para obtener uno nuevo.

```php
// app/Services/FactusService.php
public function getToken(): ?string
{
    return cache()->remember('factus:access_token', 3500, function () {
        $auth = $this->authenticate();
        if (!empty($auth['access_token'])) {
            cache()->put('factus:refresh_token', $auth['refresh_token'], 86400 * 30);
            return $auth['access_token'];
        }
        // Fallback: usar refresh_token
        $refresh = cache()->get('factus:refresh_token');
        if ($refresh) {
            $auth = $this->refreshToken($refresh);
            if (!empty($auth['access_token'])) {
                return $auth['access_token'];
            }
        }
        return null;
    });
}
```

---

## Flujo completo de envío

```php
// 1. Usuario crea una factura en la app
// Invoice::create([...], items: [...])

// 2. Usuario hace click en "Validar en Factus"
// POST /invoices/{invoice}/send

// 3. InvoiceController::send() ejecuta:
$invoice = Invoice::find($id);
$result = $factusService->validateInvoice($invoice);

// 4. FactusService hace:
// a) Construye el payload
$payload = $this->buildInvoicePayload($invoice);

// b) Persiste el reference_code
$invoice->update(['reference_code' => $payload['reference_code']]);

// c) Llama a la API
$response = Http::withToken($token)->post(
    'https://api-sandbox.factus.com.co/v2/bills/validate',
    $payload
);

// d) Procesa la respuesta
$data = $response->json()['data'];
$cufe = $data['cufe'];
$qr = $data['links']['qr'];

// e) Guarda todo en la factura
$invoice->update([
    'status' => 'sent',
    'is_validated' => true,
    'cufe' => $cufe,
    'qr_link' => $qr,
    'status_factus' => 'VALIDATED',
    'validated_at' => now(),
]);
```

---

## Estructura del JSON de envío

### Payload completo de una factura estándar

```json
{
  "reference_code": "FACT-1-5-1782163744",
  "document": "01",
  "operation_type": "10",
  "send_email": true,
  "customer": {
    "identification_document_code": "31",
    "identification": "900123456",
    "dv": "7",
    "legal_organization_code": "1",
    "tribute_code": "01",
    "company": "Empresa Demo S.A.S",
    "names": null,
    "address": "Calle 100 #15-20, Bogotá",
    "email": "cliente@empresa.com",
    "phone": "3001234567"
  },
  "items": [
    {
      "code_reference": "1",
      "name": "Servicio de consultoría",
      "quantity": "1.00",
      "discount_rate": "0.00",
      "price": "100000.00",
      "unit_measure_code": "94",
      "standard_code": "999",
      "taxes": [
        {
          "code": "01",
          "rate": "19.00"
        }
      ]
    }
  ],
  "payment_details": [
    {
      "payment_form": "2",
      "payment_method_code": "42",
      "amount": "119000.00",
      "due_date": "2026-07-22"
    }
  ]
}
```

### Descripción campo por campo

#### Nivel raíz

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `reference_code` | string | ✅ Sí | Código único de la factura en tu sistema. Evita duplicados |
| `document` | string | ❌ No | Código del tipo de documento. Default: `01` (Factura de venta) |
| `operation_type` | string | ❌ No | Tipo de operación. Default: `10` (Estándar) |
| `send_email` | boolean | ❌ No | Si enviar email al cliente. Default: `true` |
| `customer` | object | ✅ Sí | Datos del cliente (obtenido del modelo `Client`) |
| `items` | array | ✅ Sí | Array con los productos/servicios (de `InvoiceItem`) |
| `payment_details` | array | ✅ Sí | Forma y método de pago |
| `observation` | string | ❌ No | Notas de la factura (máx 250 caracteres) |
| `prepayment_details` | array | ❌ No | Anticipos |
| `establishment` | object | ❌ No | Si tienes múltiples sedes |

#### Objeto `customer`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `identification_document_code` | string | ✅ Sí | `31` (NIT), `13` (Cédula), `22` (Extranjería) |
| `identification` | string | ✅ Sí | Número de documento (sin DV ni guiones) |
| `dv` | string | ❌ No | Dígito de verificación (solo NIT) |
| `legal_organization_code` | string | ✅ Sí | `1` (Persona jurídica), `2` (Persona natural) |
| `tribute_code` | string | ✅ Sí | `01` (IVA), `04` (INC), `ZZ` (No aplica) |
| `company` | string | ⚠️ Condicional | Razón social (si es jurídica) |
| `names` | string | ⚠️ Condicional | Nombre completo (si es natural) |
| `address` | string | ❌ No | Dirección |
| `email` | string | ❌ No | Email |
| `phone` | string | ❌ No | Teléfono |
| `municipality_code` | string | ❌ No | Código DANE del municipio |

#### Array `items[]`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `code_reference` | string | ✅ Sí | Código interno del producto |
| `name` | string | ✅ Sí | Descripción del producto/servicio |
| `quantity` | string | ✅ Sí | Cantidad (máx 2 decimales, ejemplo `"1.00"`) |
| `discount_rate` | string | ❌ No | % de descuento |
| `discount_amount` | string | ❌ No | Monto de descuento |
| `price` | string | ✅ Sí | Precio unitario SIN impuestos |
| `unit_measure_code` | string | ✅ Sí | `94` = Unidad |
| `standard_code` | string | ✅ Sí | `999` = Estándar propio |
| `taxes` | array | ✅ Sí | Impuestos aplicados |
| `withholding_taxes` | array | ❌ No | Autorretenciones |

#### Array `taxes[]` dentro de cada item

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `code` | string | ✅ Sí | `01` = IVA |
| `rate` | string | ✅ Sí | Porcentaje (ejemplo: `"19.00"`) |
| `is_excluded` | boolean | ❌ No | Si está excluido de impuestos |

#### Array `payment_details[]`

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `payment_form` | string | ✅ Sí | `1` = Contado, `2` = Crédito |
| `payment_method_code` | string | ✅ Sí | `42` = Consignación, `10` = Efectivo, `47` = Transferencia |
| `reference_code` | string | ❌ No | Referencia del pago |
| `amount` | string | ✅ Sí | Monto pagado |
| `due_date` | string | ⚠️ Si `payment_form=2` | Fecha de vencimiento |

---

## Estructura del JSON de respuesta

### Respuesta exitosa

```json
{
  "status": "OK",
  "message": "Factura validada exitosamente.",
  "data": {
    "number": "INV-2026-000001",
    "reference_code": "FACT-1-5-1782163744",
    "cufe": "b6b2ce7d815d4eab32d6821687a004f319b5cce652cc8da5e88e5ac2fc7042cb7350f7d872b8eea50680cd18351f1c7f",
    "status": "VALIDATED",
    "is_validated": true,
    "validated_at": "2026-06-22 17:00:00",
    "created_at": "2026-06-22 17:00:00",
    "company": {
      "nit": "900123456",
      "dv": "7",
      "name": "Mi Empresa SAS"
    },
    "customer": { ... },
    "items": [ ... ],
    "totals": {
      "gross_amount": "100000.00",
      "taxable_amount": "100000.00",
      "tax_amount": "19000.00",
      "total": "119000.00"
    },
    "payment_details": [ ... ],
    "taxes": [ ... ],
    "links": {
      "qr": "https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey=b6b2ce7d...",
      "public_url": "https://app-sandbox.factus.com.co/documents/bills/e157abff..."
    },
    "related_notes": {
      "credit_notes": [],
      "debit_notes": []
    }
  }
}
```

### Campos clave de la respuesta

| Campo | Descripción | Importancia |
|-------|-------------|------------|
| `data.cufe` | Código Único de Facturación Electrónica | ⚠️ **CRÍTICO** - Identifica la factura ante la DIAN |
| `data.is_validated` | Si fue validada por la DIAN | true = autorizada |
| `data.status` | Estado del documento | `VALIDATED`, `REJECTED`, `PENDING` |
| `data.links.qr` | URL del código QR para verificación pública | Útil para incluir en facturas |
| `data.links.public_url` | URL pública del documento en Factus | Acceso al PDF oficial |
| `data.totals.total` | Total final aprobado | Confirmación |

### Respuesta de error

```json
{
  "status": "Error",
  "message": "La información del cliente no es válida.",
  "errors": {
    "customer.identification": [
      "El campo identification es obligatorio."
    ],
    "items.0.taxes.0.code": [
      "El código de impuesto no existe."
    ]
  }
}
```

---

## Códigos importantes

### Tipos de organización legal

| Código | Significado |
|--------|-------------|
| `1` | Persona jurídica (empresa) |
| `2` | Persona natural (individuo) |

### Tipos de documentos de identidad

| Código | Significado |
|--------|-------------|
| `13` | Cédula de ciudadanía |
| `22` | Cédula de extranjería |
| `31` | NIT |
| `42` | Documento de identificación extranjero |
| `50` | NIT de otro país |
| `91` | NUIP |

### Tipos de tributo

| Código | Significado |
|--------|-------------|
| `01` | IVA |
| `04` | INC |
| `ZZ` | No aplica |

### Tipos de operación

| Código | Significado |
|--------|-------------|
| `10` | Estándar |
| `11` | Mandatos |
| `12` | Transporte |
| `20` | Nota Crédito |
| `22` | Nota Débito |

### Tipos de documento

| Código | Significado |
|--------|-------------|
| `01` | Factura de venta |
| `02` | Factura de exportación |
| `03` | Factura de contingencia |
| `04` | Nota Crédito |
| `05` | Nota Débito |

### Formas de pago

| Código | Significado |
|--------|-------------|
| `1` | Contado |
| `2` | Crédito |

### Métodos de pago

| Código | Significado |
|--------|-------------|
| `10` | Efectivo |
| `42` | Consignación bancaria |
| `47` | Transferencia |
| `48` | Tarjeta de crédito |

### Códigos de impuesto

| Código | Significado |
|--------|-------------|
| `01` | IVA |
| `02` | IC |
| `03` | ICA |
| `04` | INC |

### Unidades de medida

| Código | Significado |
|--------|-------------|
| `94` | Unidad |
| `KGM` | Kilogramo |
| `LTR` | Litro |
| `MTR` | Metro |
| `CMT` | Centímetro |
| `MTQ` | Metro cuadrado |
| `HUR` | Hora |
| `DAY` | Día |

### Estándar de producto

| Código | Significado |
|--------|-------------|
| `999` | Estándar de adopción del contribuyente |
| `010` | GTIN (Global Trade Item Number) |
| `020` | Partida arancelaria |

---

## Estados de una factura

### Estado local (`status`)

| Estado | Significado |
|--------|-------------|
| `draft` | Borrador (no enviada a Factus) |
| `pending` | Pendiente de procesamiento |
| `sent` | Enviada a Factus (en proceso) |
| `cancelled` | Cancelada localmente o eliminada en Factus |
| `error` | Error en el envío |

### Estado en Factus (`status_factus`)

| Estado | Significado |
|--------|-------------|
| `VALIDATED` | Validada y aceptada por la DIAN |
| `REJECTED` | Rechazada por la DIAN |
| `PENDING` | Pendiente de validación DIAN |
| `NOTIFIED` | Entregada al cliente (ya no se puede eliminar) |
| `DELETED` | Eliminada en Factus (pre-cliente) |

### Flujo de estados

```
   draft ─────► sent ─────► (Factus valida)
     │            │              │
     │            │              ▼
     │            │         VALIDATED ──► NOTIFIED (cuando se envía email)
     │            │              │              │
     │            │              │              └── (INMUTABLE: solo Nota Crédito)
     │            │              ▼
     │            │         (DIAN: inmutable)
     │            │
     │            └──────► cancelled (si se elimina ANTES de entregar)
     ▼
  cancelled
```

### ⚠️ Inmutabilidad de la factura electrónica

**Una factura validada por la DIAN NO se puede eliminar ni editar.** Esto aplica tanto al botón "Editar" como al "Eliminar de Factus" en la app.

#### ¿Por qué?

```
┌──────────────────┐
│   TU APP (local)  │  ← Editable / Eliminable
└────────┬─────────┘
         │ POST /v2/bills/validate
         ▼
┌──────────────────┐
│      FACTUS       │  ← Marca como DELETED (panel admin)
└────────┬─────────┘
         │ Web Service SOAP firmado
         ▼
┌──────────────────┐
│   DIAN (Gobierno) │  ← INMUTABLE: registro fiscal permanente
└──────────────────┘
```

- **Tu app**: solo base de datos local. Puedes editarla/borrarla libremente.
- **Factus**: actúa como intermediario. Si llamas a `DELETE /v1/bills/{ref}` ANTES de que se entregue al cliente, la marca como eliminada. NO la borra de la DIAN.
- **DIAN**: una vez que firma y asigna el CUFE, el documento es un registro fiscal permanente. NO se puede borrar, modificar ni invalidar.

#### ¿Cuándo sí funciona "Eliminar de Factus"?

Solo cuando la factura cumple TODAS estas condiciones:
- ✅ Tiene `reference_code` (fue enviada a Factus)
- ✅ Estado en Factus = `VALIDATED`
- ✅ NO ha sido notificada al cliente por correo electrónico
- ✅ Estado local `status_factus` ≠ `NOTIFIED`

Si la factura **ya fue enviada al cliente por correo**, la DIAN la considera un documento entregado y NO permite eliminarla. La API de Factus devuelve un error similar a: *"La factura ya fue notificada al cliente y no puede ser eliminada"*.

#### Procedimiento correcto para corregir una factura

| Situación | Solución legal |
|-----------|---------------|
| Error en precio/cantidad ANTES de validar | Eliminar borrador local, crear nueva |
| Error en precio/cantidad DESPUÉS de validar pero ANTES de enviar al cliente | Botón "Eliminar de Factus" + crear nueva |
| Error DESPUÉS de enviar al cliente | **Crear Nota Crédito** que anule la factura original |
| Cliente canceló el servicio | **Crear Nota Crédito** |

#### Cuándo usar cada opción

```
1. ¿Factura validada por DIAN?
   └─ NO → Editar/eliminar localmente (es solo tu base de datos)

2. ¿Ya entregada al cliente?
   └─ NO → Botón "Eliminar de Factus" (se borra de Factus)

3. ¿Ya entregada al cliente?
   └─ SÍ → Crear NOTA CRÉDITO (es la única vía legal)
```

### Implementación en el código

El `FactusService::deleteInvoice()` implementa estas verificaciones:

```php
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

    // Llamada a DELETE /v1/bills/{reference_code}
    $response = $this->authed()->delete($this->baseUrl . '/v1/bills/' . urlencode($invoice->reference_code));

    if ($response->successful()) {
        $invoice->update(['status' => 'cancelled', 'status_factus' => 'DELETED']);
        return ['success' => true];
    }

    // Si la API devuelve error por notificación previa
    if (str_contains(strtolower($message), 'notified')) {
        return [
            'success' => false,
            'blocked' => true,
            'requires_credit_note' => true,
        ];
    }
}
```

### Interfaz de usuario

- **Botón "Editar"**: solo visible si `!reference_code` (factura no validada)
- **Botón "Eliminar local"**: solo visible si `!reference_code`
- **Botón "Eliminar de Factus (pre-cliente)"**: visible si `reference_code` pero requiere confirmación con 3 advertencias
- **Banner ámbar**: aparece cuando hay una validación exitosa, indicando que es inmutable
- **Botón "Crear Nota Crédito"**: solo visible si `reference_code` (factura validada)

---

## Tablas de referencia

### Detección automática de tipo de cliente

```php
$isCompany = Str::of($client->document)->upper()->startsWith(['NIT']);

if ($isCompany) {
    $documentCode = '31'; // NIT
    $legalOrgCode = '1';  // Persona jurídica
    $payload['customer']['company'] = $client->name;
} else {
    $documentCode = '13'; // Cédula
    $legalOrgCode = '2';  // Persona natural
    $payload['customer']['names'] = $client->name;
}
```

### División de NIT y dígito de verificación

```php
// Input: "900123456-7" → ['number' => '900123456', 'dv' => '7']
// Input: "900123456" → ['number' => '900123456', 'dv' => null]

protected function splitNit(?string $nit): ?array
{
    $clean = preg_replace('/[^0-9\-]/', '', $nit);
    if (str_contains($clean, '-')) {
        [$number, $dv] = explode('-', $clean, 2);
        return ['number' => $number, 'dv' => $dv];
    }
    return ['number' => $clean, 'dv' => null];
}
```

### Forma de pago según vencimiento

```php
if ($invoice->due_date) {
    // Crédito: requiere due_date
    $payload['payment_details'][] = [
        'payment_form' => '2',
        'payment_method_code' => '42',
        'amount' => $invoice->total,
        'due_date' => $invoice->due_date->toDateString(),
    ];
} else {
    // Contado: sin due_date
    $payload['payment_details'][] = [
        'payment_form' => '1',
        'payment_method_code' => '42',
        'amount' => $invoice->total,
    ];
}
```

### Impuestos por item

```php
$taxes = [];
if ($item->tax > 0) {
    $taxes[] = [
        'code' => '01', // IVA
        'rate' => number_format($item->tax, 2, '.', ''), // "19.00"
    ];
}
```

---

## Ejemplo end-to-end

### Paso 1: Crear la factura localmente

```php
$invoice = Invoice::create([
    'tenant_id' => 1,
    'client_id' => 5,
    'issue_date' => now(),
    'due_date' => now()->addDays(30),
    'status' => 'draft',
    'subtotal' => 100000,
    'tax_total' => 19000,
    'total' => 119000,
    'notes' => 'Servicio de desarrollo',
]);

$invoice->items()->create([
    'product_id' => 3,
    'description' => 'Desarrollo de feature X',
    'quantity' => 1,
    'unit_price' => 100000,
    'tax' => 19,
    'subtotal' => 100000,
]);
```

### Paso 2: Enviar a Factus

```php
$factus = app(FactusService::class);
$result = $factus->validateInvoice($invoice);

// Resultado:
// $result = [
//     'success' => true,
//     'cufe' => 'b6b2ce7d815d4eab32d6821687a004f319b5cce652cc8da5e88e5ac2fc7042cb7350f7d872b8eea50680cd18351f1c7f',
//     'qr' => 'https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey=...',
//     'status' => 'VALIDATED',
//     'public_url' => 'https://app-sandbox.factus.com.co/documents/bills/...',
// ]
```

### Paso 3: Verificar resultado

```php
$invoice->refresh();

echo $invoice->status;          // 'sent'
echo $invoice->is_validated;   // true
echo $invoice->cufe;            // 'b6b2ce7d...'
echo $invoice->qr_link;         // URL del QR
echo $invoice->status_factus;   // 'VALIDATED'
```

### Paso 4: Ver historial

```php
$invoice->integrationLogs()->each(function ($log) {
    echo "{$log->action} - {$log->status} - {$log->executed_at}";
});
// "validate_invoice - success - 2026-06-22 17:00:00"
```

---

## Acciones disponibles

### Rutas implementadas

| Método | Ruta | Acción |
|--------|------|--------|
| POST | `/invoices/{id}/send` | Validar la factura en Factus |
| POST | `/invoices/{id}/refresh-status` | Refrescar estado desde Factus |
| POST | `/invoices/{id}/cancel` | Eliminar factura en Factus |
| GET | `/invoices/{id}/factus-pdf` | Descargar PDF oficial DIAN |
| GET | `/invoices/{id}/factus-xml` | Descargar XML firmado |
| GET | `/invoices/{id}/download-pdf` | PDF local (fallback) |

### Métodos del servicio

```php
// Validar/crear factura en Factus
$result = $factusService->validateInvoice($invoice);

// Consultar estado
$status = $factusService->checkInvoiceStatus($invoice);

// Descargar archivos
$pdfContent = $factusService->downloadInvoicePdf($invoice);
$xmlContent = $factusService->downloadInvoiceXml($invoice);

// Eliminar en Factus
$result = $factusService->deleteInvoice($invoice);

// Listar rangos de numeración
$ranges = $factusService->listNumberingRanges();

// Probar conexión
$result = $factusService->testConnection();
```

---

## Errores comunes

### 1. `No se pudo autenticar con Factus`

**Causa**: Credenciales incorrectas o expiradas.

**Solución**: Verificar `.env` con `FACTUS_USERNAME`, `FACTUS_PASSWORD`, `FACTUS_CLIENT_ID`, `FACTUS_CLIENT_SECRET`.

### 2. `El campo identification es obligatorio`

**Causa**: El cliente no tiene NIT/cédula.

**Solución**: Agregar documento al cliente antes de enviar.

### 3. `El código de impuesto no existe`

**Causa**: Se está enviando un código de tax incorrecto.

**Solución**: Usar códigos válidos (`01` para IVA, `04` para INC).

### 4. `Reference code ya existe`

**Causa**: La factura ya fue enviada.

**Solución**: Verificar `reference_code` antes de reenviar.

### 5. `El subtotal no coincide con la suma de items`

**Causa**: Los totales no cuadran.

**Solución**: Llamar `$invoice->calculateTotals()` antes de enviar.

### 6. `Se encontró una factura pendiente por enviar a la DIAN`

**Causa**: Hay una factura con el mismo `reference_code` que está en proceso.

**Solución**: Eliminar primero (`DELETE /v1/bills/{reference_code}`) y reenviar.

---

## Archivos del proyecto relacionados

```
app/
├── Models/
│   └── Invoice.php                          # Modelo con campos DIAN
├── Services/
│   └── FactusService.php                     # Lógica de integración
├── Http/Controllers/Invoice/
│   └── InvoiceController.php                 # Endpoints web
├── Jobs/
│   └── SendInvoiceJob.php                    # Job asíncrono (opcional)
└── ...

config/
└── services.php                              # Credenciales

database/migrations/
└── 2026_06_22_212254_add_factus_fields_to_invoices.php

resources/views/invoice/
└── show.blade.php                            # Vista con UI de Factus

routes/
└── web.php                                   # Rutas de facturas

.env
└── FACTUS_*                                  # Variables de entorno
```

---

## Recursos externos

- **Documentación oficial**: https://developers.factus.com.co/
- **Colecciones Postman/Bruno**: https://developers.factus.com.co/coleccion
- **Autenticación**: https://developers.factus.com.co/autenticacion/auth/
- **Campos de factura**: https://developers.factus.com.co/facturas/descripcion-de-campos/
- **Crear factura**: https://developers.factus.com.co/facturas/crear-y-validar/
- **Sandbox**: `https://api-sandbox.factus.com.co`
- **Producción**: `https://api.factus.com.co`

---

## Próximos pasos

Para extender esta integración puedes:

1. **Notas Crédito**: Usar `buildCreditNotePayload()` y POST a `/v1/credit-notes/validate`
2. **Rangos de numeración**: Sincronizar `numbering_range_id` antes de enviar
3. **Suscripciones**: Webhooks para eventos de Factus
4. **Eventos de factura**: `GET /v1/bills/events/{reference}` para eventos DIAN
5. **Aceptación tácita**: `POST /v1/bills/acceptance-tacit` para facturas recibidas
