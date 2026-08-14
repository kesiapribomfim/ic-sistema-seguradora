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
            <p>Olá, <strong>{{ $apolice->segurado->seguradoPf->nome ?? $apolice->segurado->seguradoPj->razao_social }}</strong>!</p>
            
            <p>Temos uma ótima notícia: o aceite da sua cotação foi confirmado e sua apólice já está vigente!</p>
            
            <p><strong>Detalhes do seu seguro:</strong></p>
            <ul>
                <!-- TODO: Resolver -->
                <li><strong>Produto:</strong> {{ $apolice->snapshot['produto']['nome'] ?? 'Seguro' }}</li>
                <li><strong>Apólice Nº:</strong> {{ $apolice->numero_apolice }}</li>
            </ul>

            <p>Estamos enviando em anexo o documento formal da sua apólice (PDF), que contém os detalhes das coberturas e o plano de pagamento com as parcelas geradas conforme a forma escolhida.</p>
            
            <p>Guarde este documento com segurança.</p>
            <br>
            <p>Atenciosamente,<br>Equipe Seguradora</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor, não responda.</p>
        </div>
    </div>
</body>
</html>