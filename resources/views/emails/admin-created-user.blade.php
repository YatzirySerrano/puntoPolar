<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso a tu cuenta</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f5f7fb; margin:0; padding:24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e5e7eb;">
        <tr>
            <td style="background:#111827; color:#ffffff; padding:24px 28px;">
                <h1 style="margin:0; font-size:22px;">Tu cuenta ha sido creada</h1>
                <p style="margin:8px 0 0; color:#d1d5db;">Ya puedes acceder a la plataforma.</p>
            </td>
        </tr>

        <tr>
            <td style="padding:28px;">
                <p style="margin:0 0 16px; color:#111827;">Hola <strong>{{ $userName }}</strong>,</p>

                <p style="margin:0 0 16px; color:#374151;">
                    Un administrador creó tu cuenta con el rol:
                    <strong>{{ ucfirst($role) }}</strong>.
                </p>

                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px; padding:18px; margin-bottom:18px;">
                    <p style="margin:0 0 10px; color:#6b7280; font-size:13px;">Correo</p>
                    <p style="margin:0 0 16px; color:#111827; font-weight:bold;">{{ $email }}</p>

                    <p style="margin:0 0 10px; color:#6b7280; font-size:13px;">Contraseña temporal</p>
                    <p style="margin:0; color:#111827; font-weight:bold;">{{ $temporaryPassword }}</p>
                </div>

                <p style="margin:0 0 18px; color:#374151;">
                    Por seguridad, te recomendamos cambiar tu contraseña en cuanto ingreses.
                    También puedes hacerlo directamente desde este enlace:
                </p>

                <p style="margin:0 0 24px;">
                    <a href="{{ $resetUrl }}" style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:12px; font-weight:bold;">
                        Cambiar contraseña
                    </a>
                </p>

                <p style="margin:0; color:#6b7280; font-size:13px;">
                    Si no esperabas este correo, ignóralo o contacta al administrador del sistema.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
