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
        // 1. Cria automaticamente a primeira linha da timeline
        $sinistro->movimentacoes()->create([
            // O fallback (?? 1) evita que o sistema quebre caso um sinistro seja criado via Seeder/Terminal
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
        if ($sinistro->wasChanged('status') && in_array($sinistro->status, ['Aprovado'])) {

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
