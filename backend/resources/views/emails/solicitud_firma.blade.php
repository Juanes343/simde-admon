<!DOCTYPE html>
<html>
<head>
    <title>Solicitud de Firma</title>
</head>
<body>
    <h1>Hola, {{ $orden->tercero->nombre_completo ?? 'Cliente' }}</h1>
    <p>Se ha generado la Orden de Servicio #{{ $orden->id }} con fecha {{ $orden->created_at->format('d/m/Y') }}.</p>
    <p>Por favor, haz clic en el siguiente enlace para revisar y firmar digitalmente la orden:</p>
    <p>
        <a href="{{ $link }}" style="background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Firmar Orden de Servicio
        </a>
    </p>
    <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
    <p>{{ $link }}</p>
    <br>
    <p>Gracias,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
