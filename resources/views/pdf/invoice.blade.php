<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $invoice->number ?? 'Borrador' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
        .company { flex: 1; }
        .company h1 { font-size: 24px; color: #2563eb; margin-bottom: 5px; }
        .company p { font-size: 10px; color: #666; line-height: 1.6; }
        .invoice-info { text-align: right; }
        .invoice-info h2 { font-size: 20px; color: #2563eb; margin-bottom: 5px; }
        .invoice-info p { font-size: 11px; color: #666; line-height: 1.8; }
        .invoice-info .number { font-size: 14px; font-weight: bold; color: #333; }
        .addresses { display: flex; gap: 40px; margin-bottom: 30px; }
        .addresses > div { flex: 1; }
        .addresses h3 { font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .addresses p { font-size: 11px; line-height: 1.8; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table th { background: #2563eb; color: white; padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        table td { padding: 10px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        table th:last-child, table td:last-child { text-align: right; }
        table th:nth-child(3), table td:nth-child(3),
        table th:nth-child(4), table td:nth-child(4) { text-align: right; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; }
        .totals-table tr td { padding: 5px 0; border: none; }
        .totals-table tr td:last-child { text-align: right; font-weight: bold; }
        .totals-table tr.total { font-size: 16px; color: #2563eb; }
        .totals-table tr.total td { border-top: 2px solid #2563eb; padding-top: 10px; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 9px; color: #666; text-align: center; }
        .notes { margin-top: 30px; padding: 15px; background: #f8f9fa; border-left: 3px solid #2563eb; }
        .notes h4 { font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 5px; }
        .notes p { font-size: 10px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <h1>{{ $company->name }}</h1>
            <p>
                NIT: {{ $company->nit }}<br>
                {{ $company->address }}<br>
                Tel: {{ $company->phone }}<br>
                {{ $company->email }}
            </p>
        </div>
        <div class="invoice-info">
            <h2>FACTURA</h2>
            <p class="number">{{ $invoice->number ?? 'BORRADOR' }}</p>
            <p>Fecha: {{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : now()->format('d/m/Y') }}</p>
            @if($invoice->due_date)
            <p>Vence: {{ $invoice->due_date->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    <div class="addresses">
        <div>
            <h3>Facturar a</h3>
            <p>
                <strong>{{ $invoice->client->name }}</strong><br>
                @if($invoice->client->document)
                NIT: {{ $invoice->client->document }}<br>
                @endif
                @if($invoice->client->address)
                {{ $invoice->client->address }}<br>
                @endif
                @if($invoice->client->email)
                {{ $invoice->client->email }}<br>
                @endif
                @if($invoice->client->phone)
                {{ $invoice->client->phone }}
                @endif
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Precio</th>
                <th style="text-align: right;">IVA</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td style="text-align: center;">{{ number_format($item->quantity, 2) }}</td>
                <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;">{{ $item->tax }}%</td>
                <td style="text-align: right;">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>IVA:</td>
                <td>${{ number_format($invoice->tax_total, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total:</td>
                <td>${{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
    <div class="notes">
        <h4>Notas</h4>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Documento generado por Factu - Sistema de Facturación Electrónica para Colombia</p>
    </div>
</body>
</html>
