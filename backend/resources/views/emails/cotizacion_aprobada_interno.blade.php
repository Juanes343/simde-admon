<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $cotizacion->numero_cotizacion }} aprobada</title>
</head>
<body style="margin:0; padding:0; background:#f0f4f8; font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#2c3e50;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8; padding:30px 0;">
  <tr>
    <td align="center">

      <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.10);">

        <tr>
          <td style="background:#1a8a6a; padding:24px 40px; text-align:center;">
            <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.85); letter-spacing:1px;">COTIZACIÓN APROBADA Y FIRMADA</p>
            <p style="margin:6px 0 0; font-size:22px; font-weight:700; color:#ffffff;">{{ $cotizacion->numero_cotizacion }}</p>
          </td>
        </tr>

        <tr>
          <td style="padding:28px 40px 0;">
            <p style="margin:0; line-height:1.7; color:#444;">
              El cliente <strong>{{ $nombreTercero }}</strong> aprobó y firmó la cotización
              <strong>{{ $cotizacion->numero_cotizacion }}</strong>. El sistema creó automáticamente la orden de servicio
              <strong>{{ $orden->numero_orden }}</strong>, que se adjunta en PDF.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:20px 40px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d5e8f5; border-radius:6px; font-size:13px;">
              <tr>
                <td style="padding:8px 14px; color:#888; width:38%; border-bottom:1px solid #eaf3fb;">Cliente</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ $nombreTercero }} ({{ $cotizacion->tipo_id_tercero }} {{ $cotizacion->tercero_id }})
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Firmó</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ $cotizacion->firmante_nombre ?? '—' }}
                  @if($cotizacion->firmante_documento) — Doc. {{ $cotizacion->firmante_documento }} @endif
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Teléfono / celular</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">{{ $cotizacion->firmante_telefono ?? '—' }}</td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Fecha de firma</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ $cotizacion->fecha_firma ? $cotizacion->fecha_firma->setTimezone('America/Bogota')->format('d/m/Y H:i') : '—' }}
                  @if($cotizacion->firma_ip) &nbsp;·&nbsp; IP {{ $cotizacion->firma_ip }} @endif
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Orden de servicio</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;"><strong>{{ $orden->numero_orden }}</strong></td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Vigencia</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  @if($orden->fecha_inicio && $orden->fecha_fin)
                    {{ $orden->fecha_inicio->format('d/m/Y') }} — {{ $orden->fecha_fin->format('d/m/Y') }}
                    ({{ $orden->sw_prorroga_automatica == '1' ? 'con prórroga automática' : 'sin prórroga' }})
                  @else
                    Por definir (se completa al editar la orden de servicio)
                  @endif
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Periodo de facturación</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">{{ $orden->periodo_facturacion_dias }} días</td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888;">Total</td>
                <td style="padding:8px 14px;"><strong>$ {{ number_format(floatval($cotizacion->total), 2, ',', '.') }}</strong></td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 40px 28px;">
            <p style="margin:0; font-size:12px; color:#888; line-height:1.6;">
              Mensaje automático de SIMDE ADMON. También se envió copia de la orden de servicio al cliente.
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
