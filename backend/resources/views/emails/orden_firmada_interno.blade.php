<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden {{ $doc['numero_orden'] }} firmada</title>
</head>
<body style="margin:0; padding:0; background:#f0f4f8; font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#2c3e50;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8; padding:30px 0;">
  <tr>
    <td align="center">

      <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.10);">

        <tr>
          <td style="background:#1a8a6a; padding:24px 40px; text-align:center;">
            <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.85); letter-spacing:1px;">ORDEN DE SERVICIO FIRMADA</p>
            <p style="margin:6px 0 0; font-size:22px; font-weight:700; color:#ffffff;">{{ $doc['numero_orden'] }}</p>
          </td>
        </tr>

        <tr>
          <td style="padding:28px 40px 0;">
            <p style="margin:0; line-height:1.7; color:#444;">
              El cliente <strong>{{ $doc['cliente']['nombre'] }}</strong> firmó la orden de servicio
              <strong>{{ $doc['numero_orden'] }}</strong> desde el enlace de solicitud de firma. La orden firmada se adjunta en PDF.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:20px 40px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d5e8f5; border-radius:6px; font-size:13px;">
              <tr>
                <td style="padding:8px 14px; color:#888; width:38%; border-bottom:1px solid #eaf3fb;">Cliente</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ $doc['cliente']['nombre'] }} ({{ $doc['cliente']['tipo_id'] }} {{ $doc['cliente']['id'] }})
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Firmó</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ $doc['firma']['nombre'] ?? '—' }}
                  @if(!empty($doc['firma']['documento'])) — Doc. {{ $doc['firma']['documento'] }} @endif
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Fecha de firma</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ !empty($doc['firma']['fecha']) ? \Carbon\Carbon::parse($doc['firma']['fecha'])->setTimezone('America/Bogota')->format('d/m/Y H:i') : '—' }}
                  @if(!empty($doc['firma']['ip'])) &nbsp;·&nbsp; IP {{ $doc['firma']['ip'] }} @endif
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Vigencia</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">
                  {{ \Carbon\Carbon::parse($doc['fecha_inicio'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($doc['fecha_fin'])->format('d/m/Y') }}
                  ({{ $doc['prorroga'] ? 'con prórroga automática' : 'sin prórroga' }})
                </td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888; border-bottom:1px solid #eaf3fb;">Periodo de facturación</td>
                <td style="padding:8px 14px; border-bottom:1px solid #eaf3fb;">{{ $doc['periodo_dias'] }} días</td>
              </tr>
              <tr>
                <td style="padding:8px 14px; color:#888;">Total</td>
                <td style="padding:8px 14px;"><strong>$ {{ number_format($doc['totales']['total'], 2, ',', '.') }}</strong></td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 40px 28px;">
            <p style="margin:0; font-size:12px; color:#888; line-height:1.6;">
              Mensaje automático de SIMDE ADMON. También se envió copia de la orden firmada al cliente.
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
