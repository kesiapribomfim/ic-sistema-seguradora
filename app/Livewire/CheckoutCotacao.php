<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cotacao;
use Livewire\Attributes\Layout;
use App\Services\EmissaoApoliceService;

#[Layout('components.layouts.app')] 
class CheckoutCotacao extends Component
{
    public Cotacao $cotacao;
    public bool $pagamentoConcluido = false;

    //default
    public $formaPagamento = 'Cartão de Crédito';
    public $quantidadeParcelas = 1;

    public function mount(Cotacao $cotacao)
    {
        $this->cotacao = $cotacao;

        if ($this->cotacao->status === 'Aceita') {
            $this->pagamentoConcluido = true;
        }
    }

    //pega os parametros selecionados pelo cliente na pagina externa (wire:click)
    public function processarAceite (EmissaoApoliceService $service)
    {
        $this->validate([
            'formaPagamento' => 'required|string',
            'quantidadeParcelas' => 'required|integer|min:1|max:12',
        ]);

        try {
            $service->emitir(
                $this->cotacao,
                $this->formaPagamento,
                (int) $this->quantidadeParcelas

            );

            $this->pagamentoConcluido = true;

        } catch (\Exception $e) {
            //erro
            dd('O ERRO É ESTE:', $e->getMessage(), 'ARQUIVO:', $e->getFile(), 'LINHA:', $e->getLine());
        }
    
    }

    //renderiza a view blade
    public function render()
    {
        return view('livewire.checkout-cotacao');
    }
}