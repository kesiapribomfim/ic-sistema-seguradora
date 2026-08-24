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
    
    public bool $emSubscricao = false; 

    //default
    public $formaPagamento = 'Cartão de Crédito';
    public $quantidadeParcelas = 1;

    //TODO: Adicionar um link para upload de pagamento antes do aceite

    public function mount(Cotacao $cotacao)
    {
        $this->cotacao = $cotacao;

        
        if ($this->cotacao->status === 'Aceita') {
            $this->pagamentoConcluido = true;
        } elseif ($this->cotacao->status === 'Em Subscrição') {
            $this->emSubscricao = true;
        }
    }

    //pega os parametros selecionados pelo cliente na pagina externa (wire:click)
    public function processarAceite (EmissaoApoliceService $service)
    {
        if ($this->cotacao->status === 'Em Subscrição' || $this->emSubscricao) {
            $this->emSubscricao = true;
            return;
        }
        
        $this->validate([
            'formaPagamento' => 'required|string',
            'quantidadeParcelas' => 'required|integer|min:1|max:12',
        ]);
        
        try {
            // 1. Salva intenções e tenta dar o aceite
            $this->cotacao->forma_pagamento_preferida = $this->formaPagamento;
            $this->cotacao->quantidade_parcelas_preferida = (int) $this->quantidadeParcelas;
            $this->cotacao->status = 'Aceita';
            $this->cotacao->save();

            // 2. Tira a cotação da memória e puxa fresca do banco de dados
            $this->cotacao = $this->cotacao->fresh();

            // 3. Verifica o que o Observer fez com ela
            if ($this->cotacao->status === 'Em Subscrição') {
                $this->emSubscricao = true;
                return; // Corta o fluxo aqui! O cliente vê o aviso.
            }

            // 4. Se o Observer deixou passar (continuou Aceita)
            if ($this->cotacao->status === 'Aceita') {
                $service->emitir(
                    $this->cotacao,
                    $this->formaPagamento,
                    (int) $this->quantidadeParcelas
                );

                $this->pagamentoConcluido = true;
            }

        } catch (\Throwable $exception) {
            report($exception);

            $this->addError(
                'checkout',
                'Não foi possível processar sua solicitação agora. Tente novamente mais tarde.'
            );
        }
    }

    //renderiza a view blade
    public function render()
    {
        return view('livewire.checkout-cotacao');
    }
}
