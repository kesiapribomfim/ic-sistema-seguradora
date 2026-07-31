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
        // Pega a quantidade real da apólice. Se vier vazio por algum motivo, assume 1 (à vista)
        $quantidadeParcelas = $apolice->quantidade_parcelas ?? 1;
        
        // Pega o valor exato da parcela calculado no momento da cotação
        $valorParcela = $apolice->valor_parcela ?? $apolice->valor_total;

        for ($i = 1; $i <= $quantidadeParcelas; $i++) {
            
            $dataVencimento = Carbon::now()->startOfDay()->addDays(30 * $i);

            Pagamento::create([
                'apolice_id'         => $apolice->id,
                'sinistro_id'        => null, // Não há sinistro na geração da apólice
                
                // Dados da Movimentação
                'tipo_movimentacao'  => 'Recebimento',
                'valor'              => $valorParcela,
                'num_parcela'        => $i,
                
                // Datas
                'data_vencimento'    => $dataVencimento,
                'data_pagamento'     => null, // Será preenchido quando o cliente pagar
                
                // Controle
                'status'             => 'Aberta',
                'caminho_fatura_pdf' => null, // Será preenchido pelo Job do PDF depois
                'metodo_baixa'       => null, // Será preenchido na hora da confirmação do pagamento
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
