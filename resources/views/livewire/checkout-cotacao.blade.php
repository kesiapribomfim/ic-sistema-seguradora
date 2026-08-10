<div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Checkout Seguro</h1>
            <p class="text-gray-500 mt-2">Confirme os dados da sua cotação</p>
        </div>

        @if($pagamentoConcluido)
            <!-- Tela de Sucesso -->
            <div class="text-center p-6 bg-green-50 rounded-lg border border-green-200">
                <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-xl font-bold text-green-800 mb-2">Pagamento Aprovado!</h2>
                <p class="text-green-700">Sua apólice foi gerada com sucesso e já está vigente. Você receberá os documentos por e-mail.</p>
            </div>
        @else
            <!-- Resumo da Cotação -->
            <div class="space-y-4 mb-8">
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-600">Produto:</span>
                    <span class="font-medium text-gray-900">{{ $cotacao->produto->nome ?? 'Seguro' }}</span>
                </div>
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-600">Validade da Proposta:</span>
                    <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($cotacao->validade)->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between border-b pb-4 text-lg">
                    <span class="font-bold text-gray-900">Valor Total:</span>
                    <span class="font-bold text-blue-600">R$ {{ number_format($cotacao->valor_total, 2, ',', '.') }}</span>
                </div>
            </div>

            <!-- Botão de Ação -->
            <button 
                wire:click="confirmarPagamento" 
                wire:loading.attr="disabled"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex justify-center items-center"
            >
                <span wire:loading.remove>Simular Pagamento e Aceitar</span>
                <span wire:loading>Processando...</span>
            </button>
            <p class="text-xs text-gray-400 text-center mt-4">Ambiente seguro e criptografado.</p>
        @endif

    </div>
</div>