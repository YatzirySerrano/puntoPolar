<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu pedido está en camino</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:32px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px; background:#ffffff; border-radius:28px; overflow:hidden; border:1px solid #e5e7eb; box-shadow:0 18px 60px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#7dd03c 0%,#30beef 100%); padding:34px 34px 30px;">
                            <p style="margin:0; font-size:12px; letter-spacing:4px; text-transform:uppercase; font-weight:800; color:rgba(255,255,255,0.86);">
                                Mr Lana
                            </p>

                            <h1 style="margin:12px 0 0; font-size:34px; line-height:1.08; color:#ffffff; font-weight:900;">
                                Tu pedido está en camino
                            </h1>

                            <p style="margin:14px 0 0; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.92);">
                                Hola {{ $pedido->nombre_cliente }}, tu pedido ya fue preparado y entregado para envío.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px 34px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:22px; background:#f9fafb; border:1px solid #edf0f3; overflow:hidden;">
                                <tr>
                                    <td style="padding:22px;">
                                        <p style="margin:0; font-size:12px; letter-spacing:2px; text-transform:uppercase; font-weight:800; color:#6b7280;">
                                            Pedido
                                        </p>

                                        <p style="margin:8px 0 0; font-size:25px; font-weight:900; color:#111827;">
                                            {{ $pedido->folio }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
                                <tr>
                                    <td width="50%" style="padding:0 8px 0 0;">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:20px; background:#ffffff; border:1px solid #e5e7eb;">
                                            <tr>
                                                <td style="padding:18px;">
                                                    <p style="margin:0; font-size:12px; letter-spacing:1.5px; text-transform:uppercase; font-weight:800; color:#6b7280;">
                                                        Paquetería
                                                    </p>
                                                    <p style="margin:8px 0 0; font-size:17px; font-weight:900; color:#111827;">
                                                        {{ $pedido->paqueteria ?: 'Por confirmar' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td width="50%" style="padding:0 0 0 8px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:20px; background:#ffffff; border:1px solid #e5e7eb;">
                                            <tr>
                                                <td style="padding:18px;">
                                                    <p style="margin:0; font-size:12px; letter-spacing:1.5px; text-transform:uppercase; font-weight:800; color:#6b7280;">
                                                        Guía
                                                    </p>
                                                    <p style="margin:8px 0 0; font-size:17px; font-weight:900; color:#111827;">
                                                        {{ $pedido->numero_guia ?: 'Por confirmar' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($urlRastreo)
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:26px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $urlRastreo }}"
                                               style="display:inline-block; background:#30beef; color:#ffffff; text-decoration:none; padding:15px 26px; border-radius:999px; font-size:15px; font-weight:900;">
                                                Rastrear mi pedido
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <div style="margin-top:28px; padding:22px; border-radius:22px; background:#f9fafb; border:1px solid #edf0f3;">
                                <p style="margin:0; font-size:16px; font-weight:900; color:#111827;">
                                    Dirección de entrega
                                </p>

                                @if($pedido->direccion)
                                    <p style="margin:10px 0 0; font-size:14px; line-height:1.8; color:#374151;">
                                        <strong>{{ $pedido->direccion->nombre_receptor }}</strong><br>
                                        {{ $pedido->direccion->calle }} {{ $pedido->direccion->numero_exterior }}
                                        @if($pedido->direccion->numero_interior)
                                            Int. {{ $pedido->direccion->numero_interior }}
                                        @endif
                                        <br>
                                        {{ $pedido->direccion->colonia }},
                                        {{ $pedido->direccion->municipio }},
                                        {{ $pedido->direccion->estado }}<br>
                                        CP {{ $pedido->direccion->codigo_postal }}
                                    </p>

                                    @if($pedido->direccion->referencias)
                                        <p style="margin:10px 0 0; font-size:13px; line-height:1.6; color:#6b7280;">
                                            <strong>Referencias:</strong> {{ $pedido->direccion->referencias }}
                                        </p>
                                    @endif
                                @else
                                    <p style="margin:10px 0 0; font-size:14px; line-height:1.8; color:#374151;">
                                        No se encontró dirección registrada.
                                    </p>
                                @endif
                            </div>

                            <div style="margin-top:26px;">
                                <p style="margin:0; font-size:16px; font-weight:900; color:#111827;">
                                    Resumen de productos
                                </p>

                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px; border-collapse:collapse;">
                                    @foreach($pedido->items as $item)
                                        <tr>
                                            <td style="padding:14px 0; border-bottom:1px solid #edf0f3;">
                                                <p style="margin:0; font-size:14px; font-weight:800; color:#111827;">
                                                    {{ $item->nombre }}
                                                </p>
                                                <p style="margin:5px 0 0; font-size:13px; color:#6b7280;">
                                                    Cantidad: {{ $item->cantidad }}
                                                </p>
                                            </td>
                                            <td align="right" style="padding:14px 0; border-bottom:1px solid #edf0f3;">
                                                <p style="margin:0; font-size:14px; font-weight:900; color:#111827;">
                                                    ${{ number_format((float) $item->subtotal, 2) }}
                                                </p>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;">
                                <tr>
                                    <td align="right">
                                        <p style="margin:0; font-size:13px; color:#6b7280;">
                                            Total del pedido
                                        </p>
                                        <p style="margin:6px 0 0; font-size:28px; font-weight:900; color:#111827;">
                                            ${{ number_format((float) $pedido->total, 2) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:28px 0 0; font-size:14px; line-height:1.8; color:#6b7280;">
                                Si tienes dudas sobre tu envío, responde a este correo y con gusto te apoyamos.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 34px; background:#111827;">
                            <p style="margin:0; font-size:13px; line-height:1.7; color:#d1d5db;">
                                Este correo fue enviado por Mr Lana para notificar el avance de tu pedido.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0; font-size:12px; color:#9ca3af;">
                    © {{ date('Y') }} Mr Lana. Todos los derechos reservados.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
