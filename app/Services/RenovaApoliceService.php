<?php

namespace App\Services;

use App\Models\Apolice;
use App\Models\Cotacao;
use App\Jobs\RenovacaoEmailJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenovaApoliceService {

    //gerar cotação nova
    public function GerarCotacao (Apolice $apolice): ?Cotacao
    {
        return DB::transaction(function () use ($apolice){
            try {

                $produtoId = $apolice->cotacao->produto_id ?? null;

                if (!$produtoId) {
                    Log::error("Falha ao renovar: Produto não encontrado no snapshot da Apólice #{$apolice->numero_apolice}");
                    return null;
                }

                $dadosEspecificos = $apolice->dados_bem_assegurado ?? [];
                $dadosEspecificos['apolice_origem_id_temporario'] = $apolice->id;

                $novaCotacao = Cotacao::create([
                    'segurado_id'           => $apolice->segurado_id,
                    'user_id'               => $apolice->user_id, // Corretor responsável
                    'filial_id'             => $apolice->filial_id,
                    'produto_id'            => $apolice->cotacao->produto_id,
                    'cobertura_selecionada' => $apolice->snapshot['coberturas'] ?? [],
                    'dados_especificos'     => $dadosEspecificos,
                    'status'                => 'Em Elaboração', 
                    'validade'              => Carbon::now()->addDays(30),

                    'valor_total'           => $apolice->valor_total, 
                ]);

                RenovacaoEmailJob::dispatch($apolice, $novaCotacao);

                Log::info("Nova COTAÇÃO de renovação (#{$novaCotacao->id}) criada em estado 'Em elaboração' a partir da Apólice #{$apolice->numero_apolice}");
                
                return $novaCotacao;

            } catch (\Exception $e) {
                Log::error("Erro ao gerar cotação de renovação da Apólice #{$apolice->id}: " . $e->getMessage());
                return null;
            }
        });
    }
}