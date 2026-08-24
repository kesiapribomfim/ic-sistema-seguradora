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
        $sinistro = $movimentacao->sinistro;

        // Mapeia a ação da movimentação para o Status correspondente no Sinistro
        $novoStatus = match ($movimentacao->acao_realizada) {
            'Abertura' => 'Aberto',
            'Análise', 'Perícia' => 'Em análise',
            'Aprovação' => 'Aprovado',
            'Negação' => 'Negado',
            'Pagamento' => 'Pago',
            'Encerramento' => 'Encerrado',
            default => null,
        };

        // Se houver uma mudança de status válida, atualiza o pai (Sinistro)
        if ($novoStatus && $sinistro->status !== $novoStatus) {
            $sinistro->update(['status' => $novoStatus]);
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
