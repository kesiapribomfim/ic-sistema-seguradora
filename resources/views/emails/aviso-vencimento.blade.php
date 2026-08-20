<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background-color: #0056b3; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
        
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #0056b3; 
            color: #ffffff !important; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold;
            margin-top: 15px;
        }
        .btn:hover { background-color: #004494; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Seguradora Multi-ramo</h2>
        </div>
        <div class="content">
            <p>Olá, <strong>{{ $apolice->user->name }}</strong>!</p>
            
            <p>A apólice do cliente <strong>{{ $apolice->segurado->seguradoPf->nome ?? $apolice->segurado->seguradoPj->razao_social }}</strong> está próxima do vencimento!</p>
            
            <p><strong>Detalhes:</strong></p>
            <ul>
                <li><strong>Produto:</strong> {{ $apolice->snapshot['produto']['nome'] ?? 'Seguro' }}</li>
                <li><strong>Apólice Nº:</strong> {{ $apolice->numero_apolice }}</li>
                <li><strong>Data de Vencimento:</strong> {{ \Carbon\Carbon::parse($apolice->data_fim)->format('d/m/Y') }}</li>
            </ul>

            <p>Uma nova cotação de renovação foi gerada automaticamente. Acesse o link abaixo para visualizá-la e dar andamento ao processo:</p>
            
            <a href="{{ \App\Filament\Resources\CotacaoResource::getUrl('view', ['record' => $novaCotacao->id]) }}" class="btn">
                Acessar Cotação de Renovação
            </a>

            <br><br>
            <p>Atenciosamente,<br>Equipe Seguradora</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor, não responda.</p>
        </div>
    </div>
</body>
</html>