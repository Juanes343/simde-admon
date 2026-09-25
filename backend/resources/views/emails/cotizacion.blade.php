<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Cotización {{ $cotizacion->numero_cotizacion }} — SIMDE SAS</title>
</head>
<body style="margin:0; padding:0; background:#f0f4f8; font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#2c3e50;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8; padding:30px 0;">
  <tr>
    <td align="center">

      {{-- ══════════════ CONTENEDOR PRINCIPAL ══════════════ --}}
      <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.10);">

        {{-- ── CABECERA AZUL ── --}}
        <tr>
          <td style="background:linear-gradient(135deg, #1a5276 0%, #2e86c1 100%); padding:32px 40px; text-align:center;">
            <p style="margin:0; font-size:22px; font-weight:700; color:#ffffff; letter-spacing:2px;">SIMDE SAS</p>
            <p style="margin:6px 0 0; font-size:11px; color:rgba(255,255,255,0.75); letter-spacing:1px;">SOPORTE IMPLEMENTACION Y DESARROLLO</p>
            <div style="margin-top:18px; display:inline-block; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.4); border-radius:6px; padding:8px 22px;">
              <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.85); letter-spacing:1px;">COTIZACIÓN</p>
              <p style="margin:4px 0 0; font-size:20px; font-weight:700; color:#ffffff;">{{ $cotizacion->numero_cotizacion }}</p>
            </div>
          </td>
        </tr>

        {{-- ── AVISO DE COPIA INTERNA ── --}}
        @if(!empty($copiaPara))
        <tr>
          <td style="background:#fff8e1; border-bottom:1px solid #ffe082; padding:10px 40px; text-align:center; font-size:12px; color:#8a6d00;">
            Copia interna — esta cotización fue enviada a <strong>{{ $copiaPara }}</strong>.
          </td>
        </tr>
        @endif

        {{-- ── SALUDO ── --}}
        <tr>
          <td style="padding:32px 40px 0;">
            <p style="margin:0; font-size:16px; font-weight:600; color:#1a5276;">
              Estimado/a {{ $nombreTercero }},
            </p>
            <p style="margin:12px 0 0; line-height:1.7; color:#444;">
              Nos complace presentarle la siguiente cotización en respuesta a su requerimiento.
              A continuación encontrará el detalle de los servicios/productos ofertados, junto con las condiciones comerciales aplicables.
            </p>
          </td>
        </tr>

        {{-- ── RESUMEN NÚMEROS ── --}}
        <tr>
          <td style="padding:24px 40px 0;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="30%" style="background:#f0f4f8; border-radius:6px; padding:14px 16px; text-align:center; border-left:4px solid #2e86c1;">
                  <p style="margin:0; font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Fecha Emisión</p>
                  <p style="margin:5px 0 0; font-size:14px; font-weight:700; color:#1a5276;">
                    {{ \Carbon\Carbon::parse($cotizacion->fecha_emision)->format('d/m/Y') }}
                  </p>
                </td>
                <td width="4%"></td>
                <td width="30%" style="background:#f0f4f8; border-radius:6px; padding:14px 16px; text-align:center; border-left:4px solid #e74c3c;">
                  <p style="margin:0; font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Válida Hasta</p>
                  <p style="margin:5px 0 0; font-size:14px; font-weight:700; color:#c0392b;">
                    {{ $cotizacion->fecha_vencimiento ? \Carbon\Carbon::parse($cotizacion->fecha_vencimiento)->format('d/m/Y') : 'Sin vencimiento' }}
                  </p>
                </td>
                <td width="4%"></td>
                <td width="32%" style="background:#1a5276; border-radius:6px; padding:14px 16px; text-align:center;">
                  <p style="margin:0; font-size:10px; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:0.5px;">Total Cotización</p>
                  <p style="margin:5px 0 0; font-size:18px; font-weight:700; color:#ffffff;">
                    $ {{ number_format(floatval($cotizacion->total), 2, ',', '.') }}
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── CONDICIONES COMERCIALES ── --}}
        <tr>
          <td style="padding:24px 40px 0;">
            <p style="margin:0 0 10px; font-size:11px; font-weight:700; color:#1a5276; text-transform:uppercase; letter-spacing:0.5px; border-bottom:2px solid #d5e8f5; padding-bottom:5px;">
              Condiciones Comerciales
            </p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="50%" style="padding:4px 0; font-size:13px; color:#444;">
                  <span style="font-weight:600; color:#2c3e50;">Método de pago:</span>&nbsp;&nbsp;{{ $cotizacion->metodo_pago ?? '—' }}
                </td>
                <td width="50%" style="padding:4px 0; font-size:13px; color:#444;">
                  <span style="font-weight:600; color:#2c3e50;">Tipo de pago:</span>&nbsp;&nbsp;{{ $cotizacion->tipo_pago ?? '—' }}
                </td>
              </tr>
              @if($cotizacion->orden_compra)
              <tr>
                <td colspan="2" style="padding:4px 0; font-size:13px; color:#444;">
                  <span style="font-weight:600; color:#2c3e50;">Orden de compra:</span>&nbsp;&nbsp;{{ $cotizacion->orden_compra }}
                </td>
              </tr>
              @endif
            </table>
          </td>
        </tr>

        {{-- ── TABLA ÍTEMS ── --}}
        <tr>
          <td style="padding:24px 40px 0;">
            <p style="margin:0 0 0; font-size:11px; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.5px; background:#2e86c1; padding:8px 12px; border-radius:4px 4px 0 0;">
              Detalle de Servicios / Productos
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d5e8f5; border-top:none; font-size:12px;">
              <thead>
                <tr style="background:#d6eaf8;">
                  <th style="padding:7px 10px; text-align:left; color:#1a5276; font-size:10px; font-weight:700; border-bottom:1px solid #aed6f1;">#</th>
                  <th style="padding:7px 10px; text-align:left; color:#1a5276; font-size:10px; font-weight:700; border-bottom:1px solid #aed6f1;">DESCRIPCIÓN</th>
                  <th style="padding:7px 10px; text-align:center; color:#1a5276; font-size:10px; font-weight:700; border-bottom:1px solid #aed6f1;">CANT.</th>
                  <th style="padding:7px 10px; text-align:right; color:#1a5276; font-size:10px; font-weight:700; border-bottom:1px solid #aed6f1;">P. UNIT.</th>
                  <th style="padding:7px 10px; text-align:right; color:#1a5276; font-size:10px; font-weight:700; border-bottom:1px solid #aed6f1;">TOTAL</th>
                </tr>
              </thead>
              <tbody>
                @foreach($cotizacion->items as $i => $item)
                @php
                  $base    = floatval($item->subtotal) * (1 - floatval($item->porcentaje_descuento ?? 0) / 100);
                  $impPct  = floatval($item->impuesto?->porcentaje ?? 0);
                  $retPct  = floatval($item->porcentaje_ret_fuente ?? 0);
                  $total   = $base * (1 + $impPct / 100) - $base * $retPct / 100;
                  $bgRow   = $i % 2 === 0 ? '#ffffff' : '#f8fbfe';
                @endphp
                <tr style="background:{{ $bgRow }};">
                  <td style="padding:8px 10px; color:#888; border-bottom:1px solid #eaf3fb;">{{ $i + 1 }}</td>
                  <td style="padding:8px 10px; border-bottom:1px solid #eaf3fb;">
                    <strong>{{ $item->descripcion }}</strong>
                    @if($item->impuesto)
                      <br><span style="font-size:10px; background:#1a8a6a; color:#fff; padding:1px 6px; border-radius:8px;">
                        {{ $item->impuesto->nombre_impuesto ?? 'IVA' }} {{ number_format($impPct, 0) }}%
                      </span>
                    @endif
                    @if($retPct > 0)
                      <span style="font-size:10px; background:#e74c3c; color:#fff; padding:1px 6px; border-radius:8px; margin-left:3px;">
                        Ret. {{ number_format($retPct, 2) }}%
                      </span>
                    @endif
                  </td>
                  <td style="padding:8px 10px; text-align:center; border-bottom:1px solid #eaf3fb;">{{ number_format(floatval($item->cantidad), 2) }}</td>
                  <td style="padding:8px 10px; text-align:right; border-bottom:1px solid #eaf3fb;">$ {{ number_format(floatval($item->precio_unitario), 2, ',', '.') }}</td>
                  <td style="padding:8px 10px; text-align:right; font-weight:700; border-bottom:1px solid #eaf3fb;">$ {{ number_format($total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>

            {{-- Sub-totales --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d5e8f5; border-top:none; background:#f8fbfe;">
              @if(floatval($cotizacion->descuento_total) > 0)
              <tr>
                <td style="padding:5px 10px; color:#888; font-size:12px;">Subtotal</td>
                <td style="padding:5px 12px; text-align:right; font-size:12px; color:#555;">$ {{ number_format(floatval($cotizacion->subtotal), 2, ',', '.') }}</td>
              </tr>
              <tr>
                <td style="padding:5px 10px; color:#c0392b; font-size:12px;">— Descuento</td>
                <td style="padding:5px 12px; text-align:right; font-size:12px; color:#c0392b;">- $ {{ number_format(floatval($cotizacion->descuento_total), 2, ',', '.') }}</td>
              </tr>
              @endif
              @if(floatval($cotizacion->impuestos_total) > 0)
              <tr>
                <td style="padding:5px 10px; color:#1a8a6a; font-size:12px;">+ Impuestos</td>
                <td style="padding:5px 12px; text-align:right; font-size:12px; color:#1a8a6a;">$ {{ number_format(floatval($cotizacion->impuestos_total), 2, ',', '.') }}</td>
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
                <td style="padding:5px 10px; color:#e67e22; font-size:12px;">— Retención en fuente</td>
                <td style="padding:5px 12px; text-align:right; font-size:12px; color:#e67e22;">- $ {{ number_format($retencionTotal, 2, ',', '.') }}</td>
              </tr>
              @endif
              <tr>
                <td style="padding:10px 10px 10px; font-size:14px; font-weight:700; color:#1a5276; border-top:2px solid #2e86c1;">TOTAL</td>
                <td style="padding:10px 12px 10px; text-align:right; font-size:16px; font-weight:700; color:#1a5276; border-top:2px solid #2e86c1;">
                  $ {{ number_format(floatval($cotizacion->total), 2, ',', '.') }}
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── NOTAS ── --}}
        @if($cotizacion->notas)
        <tr>
          <td style="padding:20px 40px 0;">
            <div style="border-left:4px solid #2e86c1; padding:10px 16px; background:#f0f8ff; border-radius:0 4px 4px 0;">
              <p style="margin:0 0 4px; font-size:10px; font-weight:700; color:#1a5276; text-transform:uppercase; letter-spacing:0.5px;">Notas y Observaciones</p>
              <p style="margin:0; font-size:13px; color:#444; line-height:1.6;">{{ $cotizacion->notas }}</p>
            </div>
          </td>
        </tr>
        @endif

        {{-- ── APROBACIÓN EN LÍNEA ── --}}
        @if(!empty($enlaceAprobacion))
        <tr>
          <td style="padding:28px 40px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#eafaf1; border:1px solid #a9dfbf; border-radius:12px;">
              <tr>
                <td style="padding:32px 28px; text-align:center;">
                  <p style="margin:0 0 6px; font-size:17px; font-weight:700; color:#14532d;">
                    &check; Apruebe y firme su cotización
                  </p>
                  <p style="margin:0 0 22px; font-size:13px; color:#3a6b52; line-height:1.6;">
                    Revísela en línea y confírmela con su firma en pocos pasos.
                  </p>
                  <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                    <tr>
                      <td bgcolor="#1a8a6a" style="background-color:#1a8a6a; border-radius:8px;">
                        <a href="{{ $enlaceAprobacion }}" target="_blank"
                           style="display:inline-block; padding:17px 46px; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:700; color:#ffffff !important; text-decoration:none; border-radius:8px;">
                          Revisar y aprobar cotización &nbsp;&rarr;
                        </a>
                      </td>
                    </tr>
                  </table>
                  @if(!empty($enlaceExpiraEn))
                  <p style="margin:20px 0 0; font-size:11px; color:#5a8a70;">
                    El enlace estará disponible hasta el {{ $enlaceExpiraEn->copy()->setTimezone('America/Bogota')->format('d/m/Y') }}.
                  </p>
                  @endif
                  <p style="margin:10px 0 0; font-size:10px; color:#7a9a8a; word-break:break-all;">
                    Si el botón no funciona, copie este enlace en su navegador:<br>
                    <a href="{{ $enlaceAprobacion }}" style="color:#3a6b52;">{{ $enlaceAprobacion }}</a>
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

        {{-- ── MENSAJE PDF ADJUNTO ── --}}
        <tr>
          <td style="padding:24px 40px 0;">
            <div style="background:#eafaf1; border:1px solid #a9dfbf; border-radius:6px; padding:14px 18px; text-align:center;">
              <p style="margin:0; font-size:13px; color:#196f3d;">
                📎 &nbsp;Se adjunta a este correo la cotización en formato <strong>PDF</strong> para su descarga e impresión.
              </p>
            </div>
          </td>
        </tr>

        {{-- ── CIERRE ── --}}
        <tr>
          <td style="padding:28px 40px;">
            <p style="margin:0; font-size:14px; line-height:1.7; color:#444;">
              Quedamos atentos a cualquier consulta o aclaración que requiera sobre esta cotización.
              @if(!empty($enlaceAprobacion))
                Para aceptarla utilice el botón de aprobación; si requiere ajustes, puede responder directamente a este correo.
              @else
                Para aceptarla o solicitar ajustes, puede responder directamente a este correo.
              @endif
            </p>
            <p style="margin:18px 0 0; font-size:14px; color:#2c3e50;">
              Cordialmente,<br>
              <strong style="color:#1a5276;">Equipo SIMDE SAS</strong><br>
              <span style="font-size:12px; color:#888;">simdeinfo@gmail.com</span>
            </p>
          </td>
        </tr>

        {{-- ── FOOTER ── --}}
        <tr>
          <td style="background:#f8fafc; padding:22px 40px; text-align:center; border-top:1px solid #e2e8f0;">
            <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#1a5276; letter-spacing:0.5px;">
              SIMDE SAS &nbsp;·&nbsp; <a href="mailto:simdeinfo@gmail.com" style="color:#1a5276; text-decoration:none;">simdeinfo@gmail.com</a>
            </p>
            <p style="margin:0; font-size:10px; color:#94a3b8; line-height:1.7;">
              Este mensaje y sus adjuntos son de carácter confidencial y están dirigidos únicamente al destinatario indicado.<br>
              Generado automáticamente el {{ \Carbon\Carbon::now()->setTimezone('America/Bogota')->format('d/m/Y H:i') }} (hora Colombia)
            </p>
          </td>
        </tr>

      </table>
      {{-- fin contenedor --}}

    </td>
  </tr>
</table>

</body>
</html>
