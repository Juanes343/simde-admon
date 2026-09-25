<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Orden de servicio {{ $doc['numero_orden'] }} firmada — SIMDE SAS</title>
</head>
<body style="margin:0; padding:0; background:#f0f4f8; font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#2c3e50;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8; padding:30px 0;">
  <tr>
    <td align="center">

      <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.10);">

        {{-- ── CABECERA ── --}}
        <tr>
          <td style="background:linear-gradient(135deg, #1a5276 0%, #2e86c1 100%); padding:32px 40px; text-align:center;">
            <p style="margin:0; font-size:22px; font-weight:700; color:#ffffff; letter-spacing:2px;">SIMDE SAS</p>
            <p style="margin:6px 0 0; font-size:11px; color:rgba(255,255,255,0.75); letter-spacing:1px;">SOPORTE IMPLEMENTACION Y DESARROLLO</p>
            <div style="margin-top:18px; display:inline-block; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.4); border-radius:6px; padding:8px 22px;">
              <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.85); letter-spacing:1px;">ORDEN DE SERVICIO</p>
              <p style="margin:4px 0 0; font-size:20px; font-weight:700; color:#ffffff;">{{ $doc['numero_orden'] }}</p>
            </div>
          </td>
        </tr>

        {{-- ── MENSAJE ── --}}
        <tr>
          <td style="padding:32px 40px 0;">
            <p style="margin:0; font-size:16px; font-weight:600; color:#1a5276;">Estimado/a {{ $doc['cliente']['nombre'] }},</p>
            <p style="margin:12px 0 0; line-height:1.7; color:#444;">
              Hemos recibido la firma de la orden de servicio <strong>{{ $doc['numero_orden'] }}</strong>
              @if($doc['firma'] && $doc['firma']['nombre'])
                por parte de <strong>{{ $doc['firma']['nombre'] }}</strong>
              @endif
              @if($doc['firma'] && $doc['firma']['fecha'])
                el {{ \Carbon\Carbon::parse($doc['firma']['fecha'])->setTimezone('America/Bogota')->format('d/m/Y \a \l\a\s H:i') }}.
              @endif
              Adjuntamos una copia firmada en formato PDF.
            </p>
          </td>
        </tr>

        {{-- ── RESUMEN ── --}}
        <tr>
          <td style="padding:24px 40px 0;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="30%" style="background:#f0f4f8; border-radius:6px; padding:14px 16px; text-align:center; border-left:4px solid #2e86c1;">
                  <p style="margin:0; font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Inicio</p>
                  <p style="margin:5px 0 0; font-size:14px; font-weight:700; color:#1a5276;">{{ ($doc['fecha_inicio'] ? \Carbon\Carbon::parse($doc['fecha_inicio'])->format('d/m/Y') : 'Por definir') }}</p>
                </td>
                <td width="4%"></td>
                <td width="30%" style="background:#f0f4f8; border-radius:6px; padding:14px 16px; text-align:center; border-left:4px solid #e74c3c;">
                  <p style="margin:0; font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Fin</p>
                  <p style="margin:5px 0 0; font-size:14px; font-weight:700; color:#c0392b;">{{ ($doc['fecha_fin'] ? \Carbon\Carbon::parse($doc['fecha_fin'])->format('d/m/Y') : 'Por definir') }}</p>
                </td>
                <td width="4%"></td>
                <td width="32%" style="background:#1a5276; border-radius:6px; padding:14px 16px; text-align:center;">
                  <p style="margin:0; font-size:10px; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:0.5px;">Total</p>
                  <p style="margin:5px 0 0; font-size:18px; font-weight:700; color:#ffffff;">$ {{ number_format($doc['totales']['total'], 2, ',', '.') }}</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 40px 0;">
            <div style="background:#eafaf1; border:1px solid #a9dfbf; border-radius:6px; padding:14px 18px; text-align:center;">
              <p style="margin:0; font-size:13px; color:#196f3d;">
                📎 &nbsp;Se adjunta la orden de servicio firmada en formato <strong>PDF</strong>.
              </p>
            </div>
          </td>
        </tr>

        {{-- ── CIERRE ── --}}
        <tr>
          <td style="padding:28px 40px;">
            <p style="margin:0; font-size:14px; line-height:1.7; color:#444;">
              Gracias por confiar en nosotros. Ante cualquier consulta puede responder directamente a este correo.
            </p>
            <p style="margin:18px 0 0; font-size:14px; color:#2c3e50;">
              Cordialmente,<br>
              <strong style="color:#1a5276;">Equipo SIMDE SAS</strong>
            </p>
          </td>
        </tr>

        <tr>
          <td style="background:#f8fafc; padding:22px 40px; text-align:center; border-top:1px solid #e2e8f0;">
            <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#1a5276; letter-spacing:0.5px;">SIMDE SAS</p>
            <p style="margin:0; font-size:10px; color:#94a3b8; line-height:1.7;">
              Este mensaje y sus adjuntos son de carácter confidencial y están dirigidos únicamente al destinatario indicado.
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
