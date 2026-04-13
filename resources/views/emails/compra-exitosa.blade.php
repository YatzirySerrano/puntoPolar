<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compra exitosa</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h1 style="margin-bottom: 8px;">¡Gracias por tu compra! 🎉</h1>
    <p>Hola {{ $pedido->nombre_cliente }}, tu pedido <strong>#{{ $pedido->folio }}</strong> fue registrado correctamente.</p>

    <p><strong>Total:</strong> ${{ number_format((float) $pedido->total, 2) }} MXN</p>
    <p><strong>Estatus actual:</strong> {{ ucfirst($pedido->estatus) }}</p>

    <h3>Resumen</h3>
    <ul>
        @foreach($pedido->items as $item)
            <li>{{ $item->cantidad }} x {{ $item->nombre }} — ${{ number_format((float) $item->subtotal, 2) }}</li>
        @endforeach
    </ul>

    <p>Te avisaremos por correo cuando cambie el estatus de tu pedido.</p>
    <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">Este es un correo automático de Mr. Lana.</p>
</body>
</html>
