<!DOCTYPE html>
<html>
<head>
    <title>Orden de Servicio Firmada #{{ $orden->id }}</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .signature-section { margin-top: 50px; text-align: center; }
        .signature-img { max-width: 300px; border-bottom: 1px solid #000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Orden de Servicio #{{ $orden->id }}</h1>
        <p>Fecha: {{ $orden->created_at->format('d/m/Y') }}</p>
    </div>

    <div class="details">
        <h3>Detalles del Cliente</h3>
        <p><strong>Cliente:</strong> {{ $orden->tercero->nombre_completo ?? 'N/A' }}</p>
        <p><strong>Identificación:</strong> {{ $orden->tercero->numero_identificacion ?? 'N/A' }}</p>
        
        <h3>Descripción del Servicio</h3>
        <p>{{ $orden->observaciones }}</p>

        <h3>Items</h3>
        <table border="1" cellspacing="0" cellpadding="5" width="100%">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Cantidad</th>
                    <th>Valor Unitario</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orden->items as $item)
                <tr>
                    <td>{{ $item->servicio->nombre ?? 'Item' }}</td>
                    <td>{{ $item->cantidad }}</td>
                    <td>${{ number_format($item->valor_unitario, 2) }}</td>
                    <td>${{ number_format($item->valor_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="signature-section">
        <h3>Firma del Cliente</h3>
        @if($orden->firma_tercero)
            <img src="{{ $orden->firma_tercero }}" class="signature-img" alt="Firma Digital">
            <p>Firmado digitalmente el: {{ \Carbon\Carbon::parse($orden->fecha_firma)->format('d/m/Y H:i A') }}</p>
        @else
            <p>_____________ (Sin Firma) _____________</p>
        @endif
    </div>
</body>
</html>