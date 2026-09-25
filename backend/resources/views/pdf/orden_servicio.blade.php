<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Servicio {{ $doc['numero_orden'] }}</title>
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
                    <div class="doc-tipo">ORDEN DE SERVICIO</div>
                    <div class="doc-numero">{{ $doc['numero_orden'] }}</div>
                    <div class="doc-estado">
                        @if($doc['cotizacion_numero'])
                            Ref. {{ $doc['cotizacion_numero'] }}
                        @else
                            {{ $doc['estado_orden'] }}
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════════ CLIENTE / VIGENCIA / FACTURACIÓN ═══════════════════ --}}
<table class="info-section" style="margin-bottom:14px;">
    <tr>
        {{-- Cliente --}}
        <td style="width:55%; padding-right:8px; vertical-align:top;">
            <div class="info-box">
                <div class="label">Cliente</div>
                <div class="value">{{ $doc['cliente']['nombre'] }}</div>
                <div class="sub-value">
                    {{ $doc['cliente']['tipo_id'] }} {{ $doc['cliente']['id'] }}
                    @if($doc['cliente']['email'])
                        &nbsp;·&nbsp; {{ $doc['cliente']['email'] }}
                    @endif
                    @if($doc['cliente']['telefono'])
                        &nbsp;·&nbsp; Tel. {{ $doc['cliente']['telefono'] }}
                    @endif
                </div>
                @if($doc['cliente']['direccion'])
                    <div class="sub-value" style="margin-top:2px;">{{ $doc['cliente']['direccion'] }}</div>
                @endif
            </div>
        </td>

        {{-- Vigencia --}}
        <td style="width:22%; padding-right:8px; vertical-align:top;">
            <div class="info-box">
                <div class="label">Fecha Inicio</div>
                <div class="value">{{ ($doc['fecha_inicio'] ? \Carbon\Carbon::parse($doc['fecha_inicio'])->format('d/m/Y') : 'Por definir') }}</div>
            </div>
            <div class="info-box mt8">
                <div class="label">Fecha Fin</div>
                <div class="value" style="color:#c0392b;">{{ ($doc['fecha_fin'] ? \Carbon\Carbon::parse($doc['fecha_fin'])->format('d/m/Y') : 'Por definir') }}</div>
            </div>
        </td>

        {{-- Facturación --}}
        <td style="width:23%; vertical-align:top;">
            <div class="info-box">
                <div class="label">Periodo de Facturación</div>
                <div class="value" style="font-size:10px;">Cada {{ $doc['periodo_dias'] }} días</div>
            </div>
            <div class="info-box mt8">
                <div class="label">Prórroga Automática</div>
                <div class="value" style="font-size:10px;">{{ $doc['prorroga'] ? 'Sí' : 'No' }}</div>
            </div>
            @if($doc['orden_compra'])
            <div class="info-box mt8">
                <div class="label">Orden de Compra</div>
                <div class="value" style="font-size:10px;">{{ $doc['orden_compra'] }}</div>
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
        @foreach($doc['items'] as $i => $item)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $item['ref'] ?? '—' }}</td>
            <td>
                {{ $item['descripcion'] }}
                @if($item['observaciones'])
                    <br><span style="color:#777; font-size:8px;">{{ $item['observaciones'] }}</span>
                @endif
            </td>
            <td class="text-center">{{ number_format($item['cantidad'], 2) }}</td>
            <td class="text-center">{{ $item['unidad'] ?? '—' }}</td>
            <td class="text-right">$ {{ number_format($item['precio'], 2, ',', '.') }}</td>
            <td class="text-center">{{ number_format($item['descuento'], 2) }}%</td>
            <td class="text-center">
                @if($item['impuesto'])
                    <span class="imp-badge">{{ $item['impuesto'] }}</span>
                @else
                    <span style="color:#888; font-size:8px;">Excluido</span>
                @endif
                @if($item['retencion'] > 0)
                    <br><span style="color:#c0392b; font-size:8px;">Ret {{ number_format($item['retencion'], 2) }}%</span>
                @endif
            </td>
            <td class="text-right">$ {{ number_format($item['subtotal'], 2, ',', '.') }}</td>
            <td class="text-right" style="font-weight:700;">$ {{ number_format($item['total'], 2, ',', '.') }}</td>
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
                <td class="text-right">$ {{ number_format($doc['totales']['subtotal'], 2, ',', '.') }}</td>
            </tr>
            @if($doc['totales']['descuento'] > 0)
            <tr>
                <td style="color:#c0392b;">— Descuento</td>
                <td class="text-right" style="color:#c0392b;">- $ {{ number_format($doc['totales']['descuento'], 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($doc['totales']['impuestos'] > 0)
            <tr>
                <td style="color:#1a8a6a;">+ Impuestos</td>
                <td class="text-right" style="color:#1a8a6a;">$ {{ number_format($doc['totales']['impuestos'], 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($doc['totales']['retencion'] > 0)
            <tr>
                <td style="color:#e67e22;">— Retención en fuente</td>
                <td class="text-right" style="color:#e67e22;">- $ {{ number_format($doc['totales']['retencion'], 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">$ {{ number_format($doc['totales']['total'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ═══════════════════ CONDICIONES / NOTAS ═══════════════════ --}}
<div class="conditions-section">
    @if($doc['notas'])
    <div class="conditions-title">Notas y Observaciones</div>
    <div class="notes-box">{!! nl2br(e($doc['notas'])) !!}</div>
    @endif

    <div class="conditions-title" style="margin-top:12px;">Condiciones Generales</div>
    <table class="conditions-table">
        <tr>
            <td class="cond-label">Vigencia de la orden:</td>
            <td>
                @if($doc['fecha_inicio'] && $doc['fecha_fin'])
                    Del {{ \Carbon\Carbon::parse($doc['fecha_inicio'])->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($doc['fecha_fin'])->format('d/m/Y') }}
                    @if($doc['prorroga'])
                        (con prórroga automática)
                    @endif
                @else
                    Por definir
                @endif
            </td>
            <td class="cond-label" style="padding-left:20px;">Forma de pago:</td>
            <td>{{ $doc['tipo_pago'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="cond-label">Periodo de facturación:</td>
            <td>Cada {{ $doc['periodo_dias'] }} días</td>
            <td class="cond-label" style="padding-left:20px;">Cotización origen:</td>
            <td>{{ $doc['cotizacion_numero'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="cond-label">Método de pago:</td>
            <td>{{ $doc['metodo_pago'] ?? '—' }}</td>
            <td class="cond-label" style="padding-left:20px;">Orden de compra:</td>
            <td>{{ $doc['orden_compra'] ?? 'N/A' }}</td>
        </tr>
    </table>
</div>

{{-- ═══════════════════ ACEPTACIÓN Y FIRMA DEL CLIENTE ═══════════════════ --}}
@php $firma = $doc['firma']; @endphp
<div class="conditions-section" style="page-break-inside: avoid;">
    <div class="conditions-title">Aceptación del Cliente</div>
    <table class="conditions-table">
        <tr>
            <td style="width:45%; vertical-align:bottom;">
                @if($firma && $firma['imagen'])
                    <img src="{{ $firma['imagen'] }}" alt="Firma del cliente" style="max-width:220px; max-height:80px;">
                @else
                    <div style="height:60px;"></div>
                @endif
                <div style="border-top:1px solid #2c3e50; width:220px; margin-top:2px; padding-top:3px; font-size:8px; color:#777;">
                    Firma del cliente
                </div>
            </td>
            <td style="width:55%; vertical-align:top; padding-left:20px;">
                @if($firma)
                <table class="conditions-table">
                    @if($firma['nombre'])
                    <tr>
                        <td class="cond-label">Nombre:</td>
                        <td>{{ $firma['nombre'] }}</td>
                    </tr>
                    @endif
                    @if($firma['documento'])
                    <tr>
                        <td class="cond-label">Documento:</td>
                        <td>{{ $firma['documento'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($firma['telefono']))
                    <tr>
                        <td class="cond-label">Teléfono:</td>
                        <td>{{ $firma['telefono'] }}</td>
                    </tr>
                    @endif
                    @if($firma['fecha'])
                    <tr>
                        <td class="cond-label">Fecha de firma:</td>
                        <td>{{ \Carbon\Carbon::parse($firma['fecha'])->setTimezone('America/Bogota')->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($firma['ip'])
                    <tr>
                        <td class="cond-label">Dirección IP:</td>
                        <td>{{ $firma['ip'] }}</td>
                    </tr>
                    @endif
                </table>
                @else
                    <div style="font-size:10px; font-weight:700; color:#c0392b;">Pendiente de firma</div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════════════════ PIE ═══════════════════════════ --}}
<div class="footer">
    <strong>SIMDE SAS</strong> · drondon@simde.com.co<br>
    @if($doc['cotizacion_numero'] && $firma)
        Orden de servicio generada a partir de la cotización {{ $doc['cotizacion_numero'] }}, aprobada y firmada digitalmente por el cliente.<br>
    @endif
    Generado el {{ \Carbon\Carbon::now()->setTimezone('America/Bogota')->format('d/m/Y H:i') }} (hora Colombia)
</div>

</div>{{-- .page-wrapper --}}
</body>
</html>
