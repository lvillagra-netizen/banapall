@php
    $customer = $document->customer_snapshot ?? [];
    $totals = $document->totals_snapshot ?? [];
    $tax = $document->tax_snapshot ?? [];
    $currency = $totals['currency_code'] ?? $sale->currency_code;
    $annulled = $document->status === \App\Enums\DocumentStatus::Anulado;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->folio }}</title>
    <style>
        @page { margin: 34px 38px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.5px;
            color: #334155;
            line-height: 1.45;
        }
        .header {
            width: 100%;
            border-bottom: 3px solid #2f4a12;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header td { vertical-align: top; }
        .brand-name {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #1e2f0b;
            margin: 0;
        }
        .brand-sub {
            font-size: 8px;
            letter-spacing: 2.4px;
            text-transform: uppercase;
            color: #64822f;
            margin: 2px 0 0;
        }
        .company-meta {
            font-size: 9px;
            color: #64748b;
            margin-top: 8px;
            line-height: 1.6;
        }
        .folio-box {
            text-align: right;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 12px;
            width: 190px;
            margin-left: auto;
        }
        .folio-label {
            font-size: 7.5px;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .folio-number {
            font-size: 20px;
            font-weight: bold;
            color: #1e2f0b;
            margin: 3px 0;
        }
        .folio-type {
            font-size: 9px;
            color: #64748b;
        }
        .status {
            display: inline-block;
            margin-top: 6px;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .status-ok { background: #dcfce7; color: #166534; }
        .status-void { background: #ffe4e6; color: #9f1239; }
        .section-title {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 7px;
        }
        .panel {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 11px 13px;
        }
        .panel td { vertical-align: top; }
        .customer-name {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin: 0 0 2px;
        }
        .muted { color: #64748b; }
        .small { font-size: 9px; }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .items th {
            background: #f1f5e9;
            color: #3f6212;
            font-size: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            text-align: left;
            padding: 7px 8px;
            border-bottom: 1px solid #d9e5c5;
        }
        .items td {
            padding: 8px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: top;
        }
        .items .right { text-align: right; }
        .items .total-row td {
            border-bottom: none;
        }
        .totals-wrap { width: 100%; margin-top: 16px; }
        .totals {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 10px;
        }
        .totals td { padding: 4px 0; }
        .totals td:last-child { text-align: right; }
        .totals .grand {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #1e2f0b;
        }
        .notes {
            margin-top: 22px;
            padding: 10px 12px;
            border-left: 3px solid #cbd5e1;
            background: #f8fafc;
            font-size: 9px;
            color: #475569;
        }
        .footer {
            margin-top: 28px;
            border-top: 1px solid #e2e8f0;
            padding-top: 9px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.6;
        }
        .void-banner {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            margin-bottom: 12px;
            font-size: 10px;
            letter-spacing: 1.5px;
        }
    </style>
</head>
<body>
    @if ($annulled)
        <div class="void-banner">DOCUMENTO ANULADO</div>
    @endif

    <table class="header">
        <tr>
            <td>
                <p class="brand-name">{{ $company['name'] }}</p>
                <p class="brand-sub">Packing orgánico · Trazabilidad</p>
                <div class="company-meta">
                    {{ $company['activity'] }}<br>
                    {{ $company['address'] }}<br>
                    {{ $company['email'] }} · {{ $company['phone'] }}<br>
                    RUT {{ $company['tax_id'] }}
                </div>
            </td>
            <td width="210">
                <div class="folio-box">
                    <div class="folio-label"></div>
                    <div class="folio-number">{{ $document->folio }}</div>
                    <div class="folio-type">{{ $document->type->label() }}</div>
                    <div class="folio-type">{{ $document->issued_at?->format('d/m/Y H:i') }}</div>
                    @if ($annulled)
                        <span class="status status-void">{{ $document->status->label() }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table width="100%">
        <tr>
            <td width="58%" style="padding-right: 14px;">
                <p class="section-title">Cliente</p>
                <div class="panel">
                    <p class="customer-name">{{ $customer['name'] ?? $sale->customer?->name }}</p>
                    <div class="small muted" style="line-height: 1.7;">
                        @if (! empty($customer['legal_name']))
                            {{ $customer['legal_name'] }}<br>
                        @endif
                        {{ $customer['tax_id_type'] ?? 'Documento' }} {{ $customer['tax_id'] ?? '—' }}<br>
                        {{ $customer['address'] ?? '' }} {{ $customer['city'] ?? '' }}<br>
                        {{ $customer['email'] ?? '' }} · {{ $customer['phone'] ?? '' }}
                    </div>
                </div>
            </td>
            <td width="42%">
                <p class="section-title">Datos de la venta</p>
                <div class="panel">
                    <table width="100%" class="small">
                        <tr><td class="muted">Bodega</td><td align="right">{{ $sale->warehouse?->name }}</td></tr>
                        <tr><td class="muted">Vendedor</td><td align="right">{{ $sale->seller?->name }}</td></tr>
                        <tr><td class="muted">Moneda</td><td align="right">{{ $currency }} · cambio {{ number_format((float) ($totals['exchange_rate'] ?? $sale->exchange_rate), 4, ',', '.') }}</td></tr>
                        <tr><td class="muted">Impuesto</td><td align="right">{{ $tax['label'] ?? $sale->tax_mode->label() }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 48%;">Producto</th>
                <th class="right">Cantidad</th>
                <th class="right">Precio Unitario</th>
                <th class="right">Descuento</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong><br>
                        <span class="small muted">{{ $item->sku }}</span>
                    </td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',') }}</td>
                    <td class="right">{{ \App\Support\Money::format($item->unit_price, $currency) }}</td>
                    <td class="right">{{ (float) $item->discount_amount > 0 ? \App\Support\Money::format($item->discount_amount, $currency) : '—' }}</td>
                    <td class="right"><strong>{{ \App\Support\Money::format($item->line_total, $currency) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td></td>
            <td width="280">
                <table class="totals">
                    <tr><td class="muted">Neto</td><td>{{ \App\Support\Money::format($totals['subtotal'] ?? $sale->subtotal, $currency) }}</td></tr>
                    <tr><td class="muted">Descuentos</td><td>{{ \App\Support\Money::format($totals['discount_total'] ?? $sale->discount_total, $currency) }}</td></tr>
                    <tr><td class="muted">{{ $tax['label'] ?? $sale->tax_mode->label() }}</td><td>{{ \App\Support\Money::format($totals['tax_total'] ?? $sale->tax_total, $currency) }}</td></tr>
                    <tr class="grand"><td>Total</td><td>{{ \App\Support\Money::format($totals['total'] ?? $sale->total, $currency) }}</td></tr>
                    <tr><td class="muted" style="padding-top: 8px;">Pagado</td><td style="padding-top: 8px;">{{ \App\Support\Money::format($sale->paid_total, $currency) }}</td></tr>
                    <tr><td class="muted">Saldo</td><td>{{ \App\Support\Money::format($sale->balance, $currency) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($sale->notes)
        <div class="notes"><strong>Observaciones:</strong> {{ $sale->notes }}</div>
    @endif

    <div class="footer">
        {{ $company['sii_resolution'] ?? 'Resolución SII' }} · Documento generado por el prototipo BANAPALL.<br>
        Esta nota de venta es un documento simulado sin validez tributaria.<br>
        {{ $document->folio }} · {{ $company['name'] }} · {{ $document->issued_at?->format('d/m/Y H:i') }}
    </div>
</body>
</html>
