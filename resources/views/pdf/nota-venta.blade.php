<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de venta {{ $pedido->folio }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="title">Nota de venta #{{ $pedido->folio }}</div>
    <p><strong>Cliente:</strong> {{ $pedido->nombre_cliente }}</p>
    <p><strong>Correo:</strong> {{ $pedido->correo_cliente }}</p>
    <p><strong>Estatus:</strong> {{ ucfirst($pedido->estatus) }}</p>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->items as $item)
                <tr>
                    <td>{{ $item->nombre }}</td>
                    <td>{{ $item->cantidad }}</td>
                    <td>${{ number_format((float) $item->precio_unitario, 2) }}</td>
                    <td class="right">${{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="right">Total: ${{ number_format((float) $pedido->total, 2) }} MXN</h3>
</body>
</html>
