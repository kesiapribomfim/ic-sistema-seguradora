<?php

namespace App\Observers;

use App\Models\SeguradoPj;
use Illuminate\Support\Facades\Log;

class SeguradoPjObserver
{
    // nomeando user associado ao cliente
    public function saved(SeguradoPj $seguradoPj): void
    {
        Log::info("Iniciando atualização do usuário  de cnpj: {$seguradoPj->cnpj}");

        $seguradoAtualizado = $seguradoPj->segurado()->first();

        if ($seguradoAtualizado && $seguradoAtualizado->user_id) {
            // Busca o User diretamente pelo ID e atualiza
            $user = \App\Models\User::find($seguradoAtualizado->user_id);
            
            if ($user) {
                $user->updateQuietly(['name' => $seguradoPj->razao_social]);
            }
        }
    }
    /**
     * Handle the SeguradoPj "created" event.
     */
    public function created(SeguradoPj $seguradoPj): void
    {
        //
    }

    /**
     * Handle the SeguradoPj "updated" event.
     */
    public function updated(SeguradoPj $seguradoPj): void
    {
        //
    }

    /**
     * Handle the SeguradoPj "deleted" event.
     */
    public function deleted(SeguradoPj $seguradoPj): void
    {
        //
    }

    /**
     * Handle the SeguradoPj "restored" event.
     */
    public function restored(SeguradoPj $seguradoPj): void
    {
        //
    }

    /**
     * Handle the SeguradoPj "force deleted" event.
     */
    public function forceDeleted(SeguradoPj $seguradoPj): void
    {
        //
    }
}
