<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cotacao;
use App\Services\EmissaoApoliceService;

class AceiteCotacaoExterna extends Component
{
    public Cotacao $cotacao; 
    
    public $formaPagamento = 'Cartão de Crédito'; 
    public $quantidadeParcelas = 1; 
    public bool $pagamentoConcluido = false; 

    public function mount(Cotacao $cotacao)
    {
        $this->cotacao = $cotacao;
    }

    // O NOME EXATO QUE O SEU BOTÃO ESTÁ CHAMANDO:
    public function processarAceite(EmissaoApoliceService $emissaoService)
    {
        $this->validate([
            'formaPagamento' => 'required|string',
            'quantidadeParcelas' => 'required|integer|min:1|max:12',
        ]);

        try {
            $emissaoService->emitir(
                $this->cotacao, 
                $this->formaPagamento, 
                (int) $this->quantidadeParcelas
            );

            $this->pagamentoConcluido = true;

        } catch (\Exception $e) {
            session()->flash('erro', 'Houve um problema: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.aceite-cotacao-externa');
    }
}