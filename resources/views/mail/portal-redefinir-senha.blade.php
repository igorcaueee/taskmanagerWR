<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background-color: #0084AA; padding: 28px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
        .header p { color: #b3dce8; font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; font-size: 14px; line-height: 1.7; color: #374151; }
        .body a { color: #0084AA; }
        .greeting { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: #1f2937; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 28px; background-color: #0084AA; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .aviso { font-size: 12px; color: #9ca3af; margin-top: 24px; }
        .footer { background-color: #f9fafb; padding: 20px 32px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>WR Assessoria</h1>
            <p>Redefinição de senha do Portal do Cliente</p>
        </div>
        <div class="body">
            <p class="greeting">Olá, {{ $nomeUsuario }}!</p>
            <p>Recebemos um pedido para redefinir a senha do seu acesso ao Portal do Cliente. Clique no botão abaixo para criar uma nova senha:</p>

            <a href="{{ $linkRedefinir }}" class="btn">🔑 Redefinir minha senha</a>

            <p class="aviso">
                Este link expira em 60 minutos. Se você não solicitou essa redefinição, pode ignorar este e-mail —
                sua senha atual continua válida.
            </p>
        </div>
        <div class="footer">
            <p>WR Assessoria &mdash; Este é um e-mail automático.</p>
            <p style="margin-top: 4px;">© {{ date('Y') }} WR Assessoria. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
