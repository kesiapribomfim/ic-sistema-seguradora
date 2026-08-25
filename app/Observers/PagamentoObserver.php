<?php

namespace App\Observers;

use App\Models\Pagamento;

class PagamentoObserver
{
    /**
     * Handle the Pagamento "created" event.
     */
    public function created(Pagamento $pagamento): void
    {
        if ($pagamento->sinistro_id) {
            
            $sinistro = $pagamento->sinistro;

            $sinistro->update([
                'status' => 'Pago', 
                'valor_pago' => $pagamento->valor, 
            ]);

            $valorFormatado = number_format($pagamento->valor, 2, ',', '.');
            
            $sinistro->movimentacoes()->create([
                'user_id' => auth()->id() ?? 1,
                'data_hr_movimentacao' => now(),
                'acao_realizada' => 'Pagamento',
                'descricao' => "Pagamento de indenização processado e liberado pelo setor financeiro no valor de R$ {$valorFormatado}.",
            ]);
        }
    }
    /**
     * Handle the Pagamento "updated" event.
     */
    public function updated(Pagamento $pagamento): void
    {
        //
    }

    /**
     * Handle the Pagamento "deleted" event.
     */
    public function deleted(Pagamento $pagamento): void
    {
        //
    }

    /**
     * Handle the Pagamento "restored" event.
     */
    public function restored(Pagamento $pagamento): void
    {
        //
    }

    /**
     * Handle the Pagamento "force deleted" event.
     */
    public function forceDeleted(Pagamento $pagamento): void
    {
        //
    }
}
