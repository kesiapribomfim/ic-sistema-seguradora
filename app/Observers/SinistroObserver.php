<?php

namespace App\Observers;

use App\Models\Sinistro;
use App\Jobs\RecalcularScoreRiscoJob;

class SinistroObserver
{
    /**
     * Handle the Sinistro "created" event.
     */
    public function created(Sinistro $sinistro): void
    {
        $sinistro->movimentacoes()->create([
            'user_id' => auth()->id() ?? 1, 
            'data_hr_movimentacao' => now(),
            'acao_realizada' => 'Abertura',
            'descricao' => 'Abertura automática do sinistro. Relato inicial: ' . $sinistro->descricao,
        ]);
    }

    /**
     * Handle the Sinistro "updated" event.
     */
    public function updated(Sinistro $sinistro): void
    {
        if ($sinistro->wasChanged('status') && in_array($sinistro->status, ['Pago'])) {

            if ($sinistro->apolice && $sinistro->apolice->segurado_id) {
                $seguradoId = $sinistro->apolice->segurado_id;

                \App\Jobs\RecalcularScoreRiscoJob::dispatch($seguradoId);
            }
        }
    }

    // public function created(Sinistro $sinistro): void
    // {
    //     if (in_array($sinistro->status, ['Aprovado', 'Pago', 'Encerrado'])) {
    //         $seguradoId = $sinistro->apolice->segurado_id;
    //         RecalcularScoreRiscoJob::dispatch($seguradoId);
    //     }
    // }

    /**
     * Handle the Sinistro "deleted" event.
     */
    public function deleted(Sinistro $sinistro): void
    {
        //
    }

    /**
     * Handle the Sinistro "restored" event.
     */
    public function restored(Sinistro $sinistro): void
    {
        //
    }

    /**
     * Handle the Sinistro "force deleted" event.
     */
    public function forceDeleted(Sinistro $sinistro): void
    {
        //
    }
}
