<?php

namespace App\Observers;

use App\Models\Apolice;
use App\Models\Pagamento;
use App\Jobs\EnviarApoliceEmailJob;

class ApoliceObserver
{
    /**
     * Handle the Apolice "created" event.
     */
    public function created(Apolice $apolice): void
    {
        // Verifica se é uma renovação (ou seja, se possui uma apólice de origem)
        if ($apolice->apolice_origem_id !== null) {
            
            // Mudar o status da apólice velha
            $apoliceOrigem = Apolice::find($apolice->apolice_origem_id);
            if ($apoliceOrigem) {
                $apoliceOrigem->update(['status' => 'Renovada']);
            }

            // TODO: No futuro, criar uma 'RenovacaoEmailJob' específica aqui.
        }

        EnviarApoliceEmailJob::dispatch($apolice);
    }

    /**
     * Handle the Apolice "updated" event.
     */
    public function updated(Apolice $apolice): void
    {
        //
    }

    /**
     * Handle the Apolice "deleted" event.
     */
    public function deleted(Apolice $apolice): void
    {
        //
    }

    /**
     * Handle the Apolice "restored" event.
     */
    public function restored(Apolice $apolice): void
    {
        //
    }

    /**
     * Handle the Apolice "force deleted" event.
     */
    public function forceDeleted(Apolice $apolice): void
    {
        //
    }
}