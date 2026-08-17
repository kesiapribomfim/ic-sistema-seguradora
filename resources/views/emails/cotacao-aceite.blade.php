<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background-color: #0056b3; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Seguradora Multi-ramo</h2>
        </div>
        <div class="content">
            <p>Olá, <strong>{{ $cotacao->segurado->seguradoPf->nome ?? $cotacao->segurado->seguradoPj->razao_social }}</strong>!</p>
            
            <p>Sua cotação foi elaborada!</p>
            
            <p><strong>Acesse o link abaixo para aceitá-la</strong></p>
            
            <a href="{{ $cotacao->gerarLinkCheckout() }}" class="btn">
                Revisar e Aceitar Cotação
            </a>
            
            <p>O prazo para aceite da cotacao é de <strong>{{ $cotacao->validade }}.</p>
            <br>
            <p>Atenciosamente,<br>Equipe Seguradora</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor, não responda.</p>
        </div>
    </div>
</body>
</html>