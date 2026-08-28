<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        
        <!-- Cabeçalho -->
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900">
                Solicite sua Cotação
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Preencha os dados abaixo e encontre a proteção ideal para você ou seu negócio.
            </p>
        </div>

        <!-- Formulário do Filament -->
        <div class="bg-white shadow-xl sm:rounded-lg p-8">
            <form wire:submit="submit">
                
                {{ $this->form }}
                
                <div class="mt-8 pt-5 border-t border-gray-200">
                    <!-- Usando o botão nativo do Filament para manter o padrão -->
                    <x-filament::button type="submit" size="lg" class="w-full justify-center">
                        Enviar Solicitação de Cotação
                    </x-filament::button>
                </div>
                
            </form>
        </div>
        
    </div>
</div>