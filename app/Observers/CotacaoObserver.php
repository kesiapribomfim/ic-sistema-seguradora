<?php

namespace App\Observers;

use App\Models\Cotacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CotacaoObserver
{
    /**
     * Handle the Cotacao "created" event.
     */
    public function created(Cotacao $cotacao): void
    {
        //
    }

    public function updating(Cotacao $cotacao): void
    {
        if ($cotacao->isDirty('status') && $cotacao->status === 'Aceita') {
            
            $statusAnterior = $cotacao->getOriginal('status');
            //Fluxo Elaboração->Subscrição->Aceite
            if ($statusAnterior === 'Em Subscrição') {
                $usuario = \Illuminate\Support\Facades\Auth::user();
                
                if ($usuario && $usuario->hasRole('Subscritor')) {
                    return;
                } else {
                    // Se foi o cliente (sem login) ou um corretor tentando forçar a barra:
                    $cotacao->status = 'Em Subscrição'; 
                    return; 
                }
            }

            $produto = $cotacao->produto;
            $limiteAlcada = $produto->valor_alcada ?? 9999999999.99;

            $coberturas = $cotacao->cobertura_selecionada ?? [];
            $riscoTotal = collect($coberturas)->sum(fn($c) => (float) ($c['limite_maximo'] ?? 0));

            if ($riscoTotal > $limiteAlcada) {
                $cotacao->status = 'Em Subscrição';
                
                Log::info('Cotação enviada para subscrição por excesso de alçada.', [
                    'cotacao_id' => $cotacao->id,
                    'risco_total' => $riscoTotal,
                    'limite_produto' => $limiteAlcada
                ]);

                // TODO: Aqui você pode colocar um Job para mandar e-mail para a equipe de Subscrição
                // \App\Jobs\NotificarSubscritorJob::dispatch($cotacao);
            }
        }
    }

    /**
     * Handle the Cotacao "updated" event.
     */
    public function updated(Cotacao $cotacao): void
    {
        //
    }

    /**
     * Handle the Cotacao "deleted" event.
     */
    public function deleted(Cotacao $cotacao): void
    {
        //
    }

    /**
     * Handle the Cotacao "restored" event.
     */
    public function restored(Cotacao $cotacao): void
    {
        //
    }

    /**
     * Handle the Cotacao "force deleted" event.
     */
    public function forceDeleted(Cotacao $cotacao): void
    {
        //
    }
}
