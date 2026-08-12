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
            <label class="block text-gray-700 mb-2">Forma de Pagamento:</label>
            <select 
                wire:model="formaPagamento"
                class="w-full border border-gray-300 rounded-lg p-2 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="Cartão de Crédito">Cartão de Crédito</option>
                <option value="Boleto Bancário">Boleto Bancário</option>
                <option value="Pix">Pix</option>
            </select>
            <label class="block text-gray-700 mb-2">Quantidade de Parcelas:</label>
            <select 
                wire:model="quantidadeParcelas"
                class="w-full border border-gray-300 rounded-lg p-2 mb-6 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">{{ $i }}x</option>
                @endfor
            </select>
            <button 
                wire:click="processarAceite"
                type="button"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 mt-4"
            >
                Confirmar Pagamento e Emitir Apólice
            </button>
            <p class="text-xs text-gray-400 text-center mt-4">Ambiente seguro e criptografado.</p>
        @endif

    </div>
</div>