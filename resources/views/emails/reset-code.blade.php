<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Código de Recuperación</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f4f5; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .code { font-size: 36px; font-weight: bold; letter-spacing: 5px; text-align: center; color: #0891b2; background-color: #ecfeff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px dashed #a5f3fc; }
        .footer { text-align: center; font-size: 12px; color: #71717a; margin-top: 30px; }
        .text { color: #3f3f46; font-size: 16px; line-height: 1.5; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #18181b; margin: 0; font-size: 24px;">Recuperar Contraseña</h1>
        </div>
        
        <p class="text">Has solicitado restablecer tu contraseña. Usa el siguiente código para continuar:</p>
        
        <div class="code">{{ $code }}</div>
        
        <p class="text">Este código expira en 15 minutos.</p>
        <p class="text" style="font-size: 14px; color: #71717a;">Si no solicitaste esto, puedes ignorar este correo.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
