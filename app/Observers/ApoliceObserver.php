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
        // TODO: criar coluna $apolice->qtd_parcelas
        $quantidadeParcelas = 3;
        
        $valorParcela = $apolice->valor_total / $quantidadeParcelas;

        for ($i = 1; $i <= $quantidadeParcelas; $i++) {
            
            $dataVencimento = Carbon::now()->startOfDay()->addDays(30 * $i);

            Pagamento::create([
                'apolice_id'     => $apolice->id,
                'numero_parcela' => $i,
                'valor'          => $valorParcela,
                'vencimento'     => $dataVencimento,
                'status'         => 'Aberta',
            ]);
        }
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
