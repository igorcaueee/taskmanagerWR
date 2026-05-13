<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $assunto }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background-color: #0084AA; padding: 28px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
        .header p { color: #b3dce8; font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; font-size: 14px; line-height: 1.7; color: #374151; }
        .body h1, .body h2, .body h3 { color: #0084AA; margin-bottom: 10px; margin-top: 20px; }
        .body p { margin-bottom: 12px; }
        .body ul, .body ol { padding-left: 20px; margin-bottom: 12px; }
        .body li { margin-bottom: 6px; }
        .body a { color: #0084AA; }
        .footer { background-color: #f9fafb; padding: 20px 32px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { font-size: 12px; color: #9ca3af; }
        .greeting { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: #1f2937; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>WR Assessoria</h1>
            <p>{{ $assunto }}</p>
        </div>
        <div class="body">
            <p class="greeting">Olá, {{ $nomeDestinatario }}!</p>
            {!! $conteudoHtml !!}
        </div>
        <div class="footer">
            <p>WR Assessoria &mdash; Você está recebendo este e-mail por ser nosso cliente.</p>
            <p style="margin-top: 4px;">© {{ date('Y') }} WR Assessoria. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
