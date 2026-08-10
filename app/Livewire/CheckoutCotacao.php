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

    public function mount(Cotacao $cotacao)
    {
        $this->cotacao = $cotacao;

        if ($this->cotacao->status === 'Aceita') {
            $this->pagamentoConcluido = true;
        }
    }

    public function confirmarPagamento(EmissaoApoliceService $service)
    {
        $service->emitir($this->cotacao, 'Cartão de Crédito', 1);
        $this->pagamentoConcluido = true;
    }

    public function render()
    {
        return view('livewire.checkout-cotacao');
    }
}