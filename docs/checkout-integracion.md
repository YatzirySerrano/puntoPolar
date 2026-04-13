# Integración de compras: correo, PDF y Stripe

## 1) Correos de compra
- Configurar `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME`.
- Usar `App\Mail\CompraExitosaMail` al confirmar pago exitoso.
- Recomendación: enviar correo en cola (`queue`) para no bloquear UX.

## 2) PDF de nota de venta
- Servicio base: `App\Services\VentaPdfService`.
- Plantilla base PDF: `resources/views/pdf/nota-venta.blade.php`.
- Requiere instalar dompdf en proyecto (si no existe):
  - `composer require barryvdh/laravel-dompdf`

## 3) Stripe API
- Variables en `.env`:
  - `STRIPE_KEY`
  - `STRIPE_SECRET`
  - `STRIPE_WEBHOOK_SECRET`
- Config en `config/services.php` bajo `stripe`.
- Servicio base: `App\Services\StripeGatewayService`.
- Flujo recomendado:
  1. Crear `PaymentIntent` en backend.
  2. Confirmar pago en frontend con Stripe.js.
  3. Recibir webhook `payment_intent.succeeded`.
  4. Marcar pedido como pagado y enviar correo/PDF.

## 4) Seguridad y operación
- Verificar firma de webhook con `STRIPE_WEBHOOK_SECRET`.
- Registrar intentos fallidos de pago y mensajes claros al usuario.
- Nunca exponer `STRIPE_SECRET` al frontend.
- Manejar reintentos y evitar doble cobro usando idempotency keys.

## 5) UX sugerida
- Mostrar mensajes con SweetAlert2 para éxito/error en checkout.
- Mensajes de error deben indicar causa (tarjeta declinada, fondos insuficientes, conexión, etc.).
- Siempre mostrar folio de pedido al usuario al finalizar compra.
