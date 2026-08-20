<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Apólice de Seguro</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0056b3; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #0056b3; }
        .section-title { background-color: #f0f0f0; padding: 5px; font-weight: bold; margin-top: 20px; }
        table { w-full; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f9f9f9; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">Seguradora Multi-ramo</div>
        <p>Certificado de Apólice: <strong>{{ $apolice->numero_apolice }}</strong></p>
    </div>

    <div class="section-title">Dados do Segurado</div>
    <p><strong>Cliente:</strong> {{ $apolice->segurado->seguradoPf->nome ?? $apolice->segurado->seguradoPj->razao_social }}</p>
    <p><strong>Documento (CPF/CNPJ):</strong> {{ $apolice->segurado->seguradoPf->cpf ?? $apolice->segurado->seguradoPj->cnpj }}</p>

    <div class="section-title">Dados do Produto</div>
    <p><strong>Produto Contratado:</strong> {{ $apolice->cotacao->produto->nome ?? 'N/A' }}</p>
    <p><strong>Vigência:</strong> {{ \Carbon\Carbon::parse($apolice->data_inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($apolice->data_fim)->format('d/m/Y') }}</p>

    <div class="section-title">Plano de Pagamento</div>
    <table>
        <thead>
            <tr>
                <th>Parcela</th>
                <th>Vencimento</th>
                <th>Valor (R$)</th>
            </tr>
        </thead>
        <tbody>

            @foreach($apolice->pagamentos as $pagamento) 
            <tr>
                <td>{{ $pagamento->num_parcela }} / {{ $apolice->quantidade_parcelas }}</td>
                <td>{{ \Carbon\Carbon::parse($pagamento->data_vencimento)->format('d/m/Y') }}</td>
                <td>R$ {{ number_format($pagamento->valor, 2, ',', '.') }}</td>
            </tr>
            @endforeach

            {{-- TODO: INVESTIGAR ESSE ERRO:
                [2026-08-20 20:00:06] local.ERROR: foreach() argument must be of type array|object, null given (View: C:\projetos\ic-sistema-seguradora\resources\views\pdf\apolice.blade.php) {"exception":"[object] (Illuminate\\View\\ViewException(code: 0): foreach() argument must be of type array|object, null given (View: C:\\projetos\\ic-sistema-seguradora\\resources\\views\\pdf\\apolice.blade.php) at C:/projetos/ic-sistema-seguradora/storage/framework/views/4252b0d78c137f4ef729e3cc45d80439.php:42)
                [stacktrace]
                [previous exception] [object] (ErrorException(code: 0): foreach() argument must be of type array|object, null given at C:/projetos/ic-sistema-seguradora/storage/framework/views/4252b0d78c137f4ef729e3cc45d80439.php:42)
                [stacktrace] 

                -> (Dica rápida para amanhã: para arrumar isso, vai ser só colocar um $this->apolice->load('pagamentos');
                lá dentro do seu BoasVindasApoliceMail imediatamente antes da linha que chama o Pdf::loadView).
            --}}
        </tbody>
    </table>

</body>
</html>