<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $contenido['titulo'] }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:32px 12px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px; background:#ffffff; border:1px solid #e5e7eb; border-radius:24px; overflow:hidden;">
                <tr>
                    <td style="padding:30px 34px; background:linear-gradient(135deg,#062A5E 0%,#30BEEF 100%);">
                        <p style="margin:0; font-size:12px; letter-spacing:3px; text-transform:uppercase; color:#dff7ff; font-weight:800;">
                            Punto Polar
                        </p>

                        <h1 style="margin:12px 0 0; font-size:30px; line-height:1.12; color:#ffffff; font-weight:900;">
                            {{ $contenido['titulo'] }}
                        </h1>

                        <p style="margin:14px 0 0; font-size:15px; line-height:1.7; color:#ffffff;">
                            Hola {{ $pedido->nombre_cliente }}, {{ $contenido['mensaje'] }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:30px 34px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:18px;">
                            <tr>
                                <td style="padding:20px;">
                                    <p style="margin:0; color:#6b7280; font-size:12px; letter-spacing:2px; text-transform:uppercase; font-weight:800;">
                                        Pedido
                                    </p>

                                    <p style="margin:8px 0 0; color:#111827; font-size:24px; font-weight:900;">
                                        {{ $pedido->folio }}
                                    </p>

                                    <p style="margin:8px 0 0; display:inline-block; background:#e0f7ff; color:#062A5E; padding:7px 12px; border-radius:999px; font-size:12px; font-weight:900; text-transform:uppercase;">
                                        {{ $contenido['badge'] }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
                            <tr>
                                <td width="50%" style="padding-right:8px;">
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:16px;">
                                        <tr>
                                            <td style="padding:18px;">
                                                <p style="margin:0; color:#6b7280; font-size:12px; text-transform:uppercase; font-weight:800;">
                                                    Total
                                                </p>
                                                <p style="margin:8px 0 0; color:#111827; font-size:20px; font-weight:900;">
                                                    ${{ number_format((float) $pedido->total, 2) }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                <td width="50%" style="padding-left:8px;">
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:16px;">
                                        <tr>
                                            <td style="padding:18px;">
                                                <p style="margin:0; color:#6b7280; font-size:12px; text-transform:uppercase; font-weight:800;">
                                                    Fecha
                                                </p>
                                                <p style="margin:8px 0 0; color:#111827; font-size:15px; font-weight:900;">
                                                    {{ now()->format('d/m/Y H:i') }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top:24px; padding:20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:18px;">
                            <p style="margin:0; color:#111827; font-size:17px; font-weight:900;">
                                Método de entrega
                            </p>

                            @if($pedido->tipo_entrega === 'recoleccion')
                                <p style="margin:10px 0 0; color:#374151; font-size:14px; line-height:1.8;">
                                    <strong>Recolección en Punto Polar</strong><br>
                                    @if($pedido->codigo_recoleccion)
                                        Código de recolección:
                                        <strong>{{ $pedido->codigo_recoleccion }}</strong><br>
                                    @endif

                                    @if($pedido->listo_para_recoger_en)
                                        Listo desde:
                                        <strong>{{ $pedido->listo_para_recoger_en->format('d/m/Y H:i') }}</strong>
                                    @else
                                        Te avisaremos cuando tu pedido esté listo para recoger.
                                    @endif
                                </p>
                            @else
                                <p style="margin:10px 0 0; color:#374151; font-size:14px; line-height:1.8;">
                                    <strong>Entrega local propia</strong><br>

                                    @if($pedido->fecha_entrega_programada)
                                        Fecha programada:
                                        <strong>{{ $pedido->fecha_entrega_programada->format('d/m/Y H:i') }}</strong><br>
                                    @endif

                                    @if($pedido->zona_entrega)
                                        Zona:
                                        <strong>{{ $pedido->zona_entrega }}</strong><br>
                                    @endif

                                    @if($pedido->salio_a_entrega_en)
                                        Salió a entrega:
                                        <strong>{{ $pedido->salio_a_entrega_en->format('d/m/Y H:i') }}</strong>
                                    @else
                                        Te avisaremos cuando tu pedido salga a entrega.
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div style="margin-top:24px;">
                            <p style="margin:0; color:#111827; font-size:17px; font-weight:900;">
                                Productos
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px; border-collapse:collapse;">
                                @foreach($pedido->items as $item)
                                    <tr>
                                        <td style="padding:14px 0; border-bottom:1px solid #e5e7eb;">
                                            <p style="margin:0; color:#111827; font-size:14px; font-weight:800;">
                                                {{ $item->nombre }}
                                            </p>
                                            <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
                                                Cantidad: {{ $item->cantidad }}
                                            </p>
                                        </td>
                                        <td align="right" style="padding:14px 0; border-bottom:1px solid #e5e7eb;">
                                            <p style="margin:0; color:#111827; font-size:14px; font-weight:900;">
                                                ${{ number_format((float) $item->subtotal, 2) }}
                                            </p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>

                        @if($pedido->direccion)
                            <div style="margin-top:24px; padding:20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:18px;">
                                <p style="margin:0; color:#111827; font-size:17px; font-weight:900;">
                                    Dirección de entrega
                                </p>

                                <p style="margin:10px 0 0; color:#374151; font-size:14px; line-height:1.8;">
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

                                    @if($pedido->direccion->referencias)
                                        <br>
                                        Referencias: {{ $pedido->direccion->referencias }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        <p style="margin:26px 0 0; color:#6b7280; font-size:14px; line-height:1.7;">
                            Gracias por comprar en Punto Polar.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 34px; background:#f9fafb; border-top:1px solid #e5e7eb;">
                        <p style="margin:0; color:#9ca3af; font-size:12px; line-height:1.6;">
                            Este correo fue generado automáticamente para notificar el avance de tu pedido.
                        </p>
                    </td>
                </tr>
            </table>

            <p style="margin:18px 0 0; color:#9ca3af; font-size:12px;">
                © {{ date('Y') }} Punto Polar
            </p>
        </td>
    </tr>
</table>
</body>
</html>