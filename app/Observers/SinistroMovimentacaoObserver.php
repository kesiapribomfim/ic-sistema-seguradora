<?php

namespace App\Observers;

use App\Models\SinistroMovimentacao;

class SinistroMovimentacaoObserver
{
    /**
     * Handle the SinistroMovimentacao "created" event.
     */
    public function created(SinistroMovimentacao $movimentacao): void
    {
        // 1. Mapeamento de "Ação" para o "Status Oficial" do Sinistro
        $statusMap = [
            'Abertura' => 'Aberto',
            'Análise' => 'Em análise',
            'Perícia' => 'Em perícia',
            'Aprovação' => 'Aprovado',
            'Negação' => 'Negado',
            'Pagamento' => 'Pago',
            'Encerramento' => 'Encerrado',
        ];

        // 2. Busca no dicionário acima qual deve ser o novo status
        $novoStatus = $statusMap[$movimentacao->acao_realizada] ?? null;

        if ($novoStatus) {
            // 3. Atualiza o Sinistro Pai
            // Usar update() direto na query é mais seguro e evita loops infinitos de Observers
            $movimentacao->sinistro()->update(['status' => $novoStatus]);
        }
    }

    /**
     * Handle the SinistroMovimentacao "updated" event.
     */
    public function updated(SinistroMovimentacao $sinistroMovimentacao): void
    {
        //
    }

    /**
     * Handle the SinistroMovimentacao "deleted" event.
     */
    public function deleted(SinistroMovimentacao $sinistroMovimentacao): void
    {
        //
    }

    /**
     * Handle the SinistroMovimentacao "restored" event.
     */
    public function restored(SinistroMovimentacao $sinistroMovimentacao): void
    {
        //
    }

    /**
     * Handle the SinistroMovimentacao "force deleted" event.
     */
    public function forceDeleted(SinistroMovimentacao $sinistroMovimentacao): void
    {
        //
    }
}
