<h2>Olá, {{ $pagamento->apolice->segurado->nome }}!</h2>
<p>Lembramos que a parcela <strong>{{ $pagamento->num_parcela }}</strong> da sua apólice {{ $pagamento->apolice->numero_apolice }} vencerá em breve.</p>
<p><strong>Vencimento:</strong> {{ $pagamento->data_vencimento->format('d/m/Y') }}</p>
<p><strong>Valor:</strong> R$ {{ number_format($pagamento->valor, 2, ',', '.') }}</p>
<p>Por favor, desconsidere este e-mail caso já tenha efetuado o pagamento.</p>