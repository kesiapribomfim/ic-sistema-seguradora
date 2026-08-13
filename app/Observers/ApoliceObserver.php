<?php

namespace App\Observers;

use App\Models\Apolice;
use App\Models\Pagamento;
use Carbon\Carbon;

class ApoliceObserver
{
    /**
     * Handle the Apolice "created" event.
     */
    public function created(Apolice $apolice): void
    {
        // Verifica se é uma renovação (ou seja, se possui uma apólice de origem)
        if ($apolice->apolice_origem_id !== null) {
            
            // 1. EFEITO COLATERAL: Mudar o status da apólice velha
            $apoliceOrigem = Apolice::find($apolice->apolice_origem_id);
            if ($apoliceOrigem) {
                $apoliceOrigem->update(['status' => 'Renovada']);
            }

            // 2. EFEITO COLATERAL: Disparar e-mail de agradecimento pela fidelidade
            // Mail::to($apolice->user->email)->send(new RenovacaoSucessoMail($apolice));
            
        } else {
            // Se NÃO tem apólice de origem, é uma apólice NOVA.
            // Dispara e-mail de boas-vindas padrão.
            // Mail::to($apolice->user->email)->send(new BoasVindasSeguradoMail($apolice));
        }

        // 3. EFEITO COLATERAL GLOBAL (Para ambas): Colocar na fila o Job para gerar o PDF
        // GerarPdfApoliceJob::dispatch($apolice);
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
