<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $cotizacion->numero_cotizacion }}</title>
    <style>
        @page {
            margin: 0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #2c3e50;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            padding: 10mm;
        }

        /* ── Encabezado ── */
        .header {
            width: 100%;
            border-bottom: 3px solid #1a5276;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }
        .header-table { width: 100%; }
        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #1a5276;
            letter-spacing: 1px;
        }
        .company-info { font-size: 9px; color: #555; margin-top: 3px; line-height: 1.5; }
        .doc-box {
            background: #1a5276;
            color: #fff;
            text-align: center;
            padding: 10px 14px;
            border-radius: 4px;
        }
        .doc-box .doc-tipo { font-size: 11px; font-weight: 600; letter-spacing: 1px; }
        .doc-box .doc-numero { font-size: 18px; font-weight: 700; margin-top: 4px; }
        .doc-box .doc-estado {
            display: inline-block;
            margin-top: 6px;
            font-size: 9px;
            padding: 2px 8px;
            border-radius: 10px;
            background: rgba(255,255,255,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ── Sección cliente / fechas ── */
        .info-section {
            width: 100%;
            margin-bottom: 16px;
        }
        .info-box {
            border: 1px solid #d5d8dc;
            border-radius: 4px;
            padding: 10px 12px;
        }
        .info-box .label {
            font-size: 8px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-box .value { font-size: 11px; font-weight: 600; color: #1a5276; }
        .info-box .sub-value { font-size: 9px; color: #555; margin-top: 2px; }

        /* ── Tabla de ítems ── */
        .items-title {
            background: #1a5276;
            color: #fff;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
            margin-bottom: 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .items-table thead tr {
            background: #2e86c1;
            color: #fff;
        }
        .items-table thead th {
            padding: 6px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #2874a6;
        }
        .items-table tbody tr:nth-child(even) { background: #f0f4f8; }
        .items-table tbody tr:nth-child(odd)  { background: #fff; }
        .items-table tbody td {
            padding: 7px 8px;
            font-size: 9px;
            border: 1px solid #d5d8dc;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── Impuesto badge ── */
        .imp-badge {
            background: #1a8a6a;
            color: #fff;
            padding: 1px 5px;
            border-radius: 8px;
            font-size: 8px;
        }

        /* ── Totales ── */
        .totales-section { width: 100%; margin-top: 0; }
        .totales-table { width: 100%; border-collapse: collapse; }
        .totales-table td { padding: 5px 10px; font-size: 10px; }
        .totales-table .total-row td {
            font-size: 13px;
            font-weight: 700;
            color: #1a5276;
            border-top: 2px solid #1a5276;
            padding-top: 8px;
        }
        .totales-wrapper {
            float: right;
            width: 38%;
            border: 1px solid #d5d8dc;
            border-radius: 0 0 4px 4px;
            overflow: hidden;
        }

        /* ── Condiciones y notas ── */
        .conditions-section {
            width: 100%;
            margin-top: 18px;
            clear: both;
        }
        .conditions-title {
            font-size: 9px;
            font-weight: 700;
            color: #1a5276;
            text-transform: uppercase;
            border-bottom: 1px solid #1a5276;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .conditions-table { width: 100%; }
        .conditions-table td {
            font-size: 9px;
            color: #444;
            padding: 3px 0;
            vertical-align: top;
        }
        .cond-label { font-weight: 700; width: 130px; color: #2c3e50; }

        /* ── Notas ── */
        .notes-box {
            border-left: 3px solid #1a5276;
            padding: 8px 12px;
            background: #f8f9fa;
            font-size: 9px;
            color: #444;
            margin-top: 10px;
            line-height: 1.5;
        }

        /* ── Pie de página ── */
        .footer {
            margin-top: 30px;
            border-top: 2px solid #1a5276;
            padding-top: 8px;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
        .validity-badge {
            display: inline-block;
            background: #f0b27a;
            color: #784212;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 700;
            margin-top: 4px;
        }

        /* Helpers */
        .clearfix::after { content: ""; display: table; clear: both; }
        .mt8 { margin-top: 8px; }
        .mb0 { margin-bottom: 0; }
    </style>
</head>
<body>
<div class="page-wrapper">

{{-- ═══════════════════════════ ENCABEZADO ═══════════════════════════ --}}
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:65%; vertical-align:middle;">
                <div class="company-name">SIMDE SAS</div>
                <div class="company-info">
                    Soporte Implementacion y Desarrollo<br>
                    drondon@simde.com.co
                </div>
            </td>
            <td style="width:35%; vertical-align:middle; text-align:right;">
                <div class="doc-box">
                    <div class="doc-tipo">COTIZACIÓN</div>
                    <div class="doc-numero">{{ $cotizacion->numero_cotizacion }}</div>
                    <div class="doc-estado">{{ strtoupper($cotizacion->sw_estado) }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════════ CLIENTE / FECHAS / CONDICIONES ═══════════════════ --}}
<table class="info-section" style="margin-bottom:14px;">
    <tr>
        {{-- Cliente --}}
        <td style="width:55%; padding-right:8px; vertical-align:top;">
            <div class="info-box">
                <div class="label">Cliente</div>
                @php $tercero = $cotizacion->tercero; @endphp
                <div class="value">{{ $tercero?->nombre_tercero ?? $cotizacion->tercero_id }}</div>
                <div class="sub-value">
                    {{ $cotizacion->tipo_id_tercero }} {{ $cotizacion->tercero_id }}
                    @if($tercero?->email)
                        &nbsp;·&nbsp; {{ $tercero->email }}
                    @endif
                    @if($tercero?->telefono)
                        &nbsp;·&nbsp; Tel. {{ $tercero->telefono }}
                    @endif
                </div>
                @if($tercero?->direccion)
                    <div class="sub-value" style="margin-top:2px;">{{ $tercero->direccion }}</div>
                @endif
            </div>
        </td>

        {{-- Fechas --}}
        <td style="width:22%; padding-right:8px; vertical-align:top;">
            <div class="info-box">
                <div class="label">Fecha Emisión</div>
                <div class="value">{{ \Carbon\Carbon::parse($cotizacion->fecha_emision)->format('d/m/Y') }}</div>
            </div>
            <div class="info-box mt8">
                <div class="label">Válida Hasta</div>
                <div class="value" style="color:#c0392b;">
                    {{ $cotizacion->fecha_vencimiento ? \Carbon\Carbon::parse($cotizacion->fecha_vencimiento)->format('d/m/Y') : '—' }}
                </div>
            </div>
        </td>

        {{-- Condiciones --}}
        <td style="width:23%; vertical-align:top;">
            <div class="info-box">
                <div class="label">Método de Pago</div>
                <div class="value" style="font-size:10px;">{{ $cotizacion->metodo_pago ?? '—' }}</div>
            </div>
            <div class="info-box mt8">
                <div class="label">Tipo de Pago</div>
                <div class="value" style="font-size:10px;">{{ $cotizacion->tipo_pago ?? '—' }}</div>
            </div>
            @if($cotizacion->orden_compra)
            <div class="info-box mt8">
                <div class="label">Orden de Compra</div>
                <div class="value" style="font-size:10px;">{{ $cotizacion->orden_compra }}</div>
            </div>
            @endif
        </td>
    </tr>
</table>

{{-- ═══════════════════════════ ÍTEMS ═══════════════════════════ --}}
<div class="items-title">Detalle de Servicios / Productos</div>
<table class="items-table">
    <thead>
        <tr>
            <th class="text-center" style="width:4%">#</th>
            <th style="width:7%">REF</th>
            <th style="width:32%">DESCRIPCIÓN</th>
            <th class="text-center" style="width:7%">CANT.</th>
            <th class="text-center" style="width:7%">UNIDAD</th>
            <th class="text-right" style="width:10%">P. UNIT.</th>
            <th class="text-center" style="width:7%">DESC %</th>
            <th class="text-center" style="width:9%">IMPUESTO</th>
            <th class="text-right" style="width:9%">SUBTOTAL</th>
            <th class="text-right" style="width:9%">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->items as $i => $item)
        @php
            $subtotal   = floatval($item->subtotal);
            $baseNeta   = $subtotal * (1 - floatval($item->porcentaje_descuento ?? 0) / 100);
            $impPct     = floatval($item->impuesto?->porcentaje ?? 0);
            $retPct     = floatval($item->porcentaje_ret_fuente ?? 0);
            $total      = $baseNeta * (1 + $impPct / 100) - $baseNeta * $retPct / 100;
        @endphp
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $item->referencia ?? '—' }}</td>
            <td>
                {{ $item->descripcion }}
                @if($item->observaciones)
                    <br><span style="color:#777; font-size:8px;">{{ $item->observaciones }}</span>
                @endif
            </td>
            <td class="text-center">{{ number_format(floatval($item->cantidad), 2) }}</td>
            <td class="text-center">{{ $item->tipo_unidad ?? '—' }}</td>
            <td class="text-right">$ {{ number_format(floatval($item->precio_unitario), 2, ',', '.') }}</td>
            <td class="text-center">{{ number_format(floatval($item->porcentaje_descuento ?? 0), 2) }}%</td>
            <td class="text-center">
                @if($item->impuesto)
                    <span class="imp-badge">{{ $item->impuesto->nombre_impuesto ?? 'IVA' }} {{ number_format($impPct, 0) }}%</span>
                @else
                    <span style="color:#888; font-size:8px;">Excluido</span>
                @endif
                @if($retPct > 0)
                    <br><span style="color:#c0392b; font-size:8px;">Ret {{ number_format($retPct, 2) }}%</span>
                @endif
            </td>
            <td class="text-right">$ {{ number_format($subtotal, 2, ',', '.') }}</td>
            <td class="text-right" style="font-weight:700;">$ {{ number_format($total, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══════════════════════════ TOTALES ═══════════════════════════ --}}
<div class="clearfix">
    <div class="totales-wrapper">
        <table class="totales-table">
            <tr>
                <td style="color:#555;">Subtotal</td>
                <td class="text-right">$ {{ number_format(floatval($cotizacion->subtotal), 2, ',', '.') }}</td>
            </tr>
            @if(floatval($cotizacion->descuento_total) > 0)
            <tr>
                <td style="color:#c0392b;">— Descuento</td>
                <td class="text-right" style="color:#c0392b;">- $ {{ number_format(floatval($cotizacion->descuento_total), 2, ',', '.') }}</td>
            </tr>
            @endif
            @if(floatval($cotizacion->impuestos_total) > 0)
            <tr>
                <td style="color:#1a8a6a;">+ Impuestos</td>
                <td class="text-right" style="color:#1a8a6a;">$ {{ number_format(floatval($cotizacion->impuestos_total), 2, ',', '.') }}</td>
            </tr>
            @endif
            @php
                $retencionTotal = $cotizacion->items->sum(function($item) {
                    $base = floatval($item->subtotal) * (1 - floatval($item->porcentaje_descuento ?? 0) / 100);
                    return $base * floatval($item->porcentaje_ret_fuente ?? 0) / 100;
                });
            @endphp
            @if($retencionTotal > 0)
            <tr>
                <td style="color:#e67e22;">— Retención en fuente</td>
                <td class="text-right" style="color:#e67e22;">- $ {{ number_format($retencionTotal, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">$ {{ number_format(floatval($cotizacion->total), 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ═══════════════════ CONDICIONES / NOTAS ═══════════════════ --}}
<div class="conditions-section">
    @if($cotizacion->notas)
    <div class="conditions-title">Notas y Observaciones</div>
    <div class="notes-box">{!! nl2br(e($cotizacion->notas)) !!}</div>
    @endif

    <div class="conditions-title" style="margin-top:12px;">Condiciones Generales</div>
    <table class="conditions-table">
        <tr>
            <td class="cond-label">Validez de la oferta:</td>
            <td>
                @if($cotizacion->fecha_vencimiento)
                    Hasta el {{ \Carbon\Carbon::parse($cotizacion->fecha_vencimiento)->format('d/m/Y') }}
                @else
                    30 días a partir de la fecha de emisión
                @endif
            </td>
            <td class="cond-label" style="padding-left:20px;">Forma de pago:</td>
            <td>{{ $cotizacion->tipo_pago ?? 'Contado' }}</td>
        </tr>
        <tr>
            <td class="cond-label">Método de pago:</td>
            <td>{{ $cotizacion->metodo_pago ?? '—' }}</td>
            <td class="cond-label" style="padding-left:20px;">Orden de compra:</td>
            <td>{{ $cotizacion->orden_compra ?? 'N/A' }}</td>
        </tr>
    </table>
</div>

{{-- ═══════════════════════════ PIE ═══════════════════════════ --}}
<div class="footer">
    <strong>SIMDE SAS</strong> · drondon@simde.com.co<br>
    Este documento es una cotización y no constituye una factura de venta.<br>
    Generado el {{ \Carbon\Carbon::now()->setTimezone('America/Bogota')->format('d/m/Y H:i') }} (hora Colombia)
</div>

</div>{{-- .page-wrapper --}}
</body>
</html>
