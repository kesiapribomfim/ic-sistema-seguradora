<?php

namespace App\Services;

use App\Jobs\RenovacaoEmailJob;
use App\Models\Apolice;
use App\Models\Cotacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenovaApoliceService
{
    public function gerarCotacao(Apolice $apolice): ?Cotacao
    {
        $produtoId = $apolice->cotacao->produto_id ?? null;

        if (! $produtoId) {
            Log::error("Falha ao renovar: Produto não encontrado no snapshot da Apólice #{$apolice->numero_apolice}");
            return null;
        }
                
        try {
            return DB::transaction(function () use ($apolice){
                
                $novaCotacao = $this->criarCotacaiRenovacao($apolice);

                $this->enviarEmail($apolice, $novaCotacao);

                return $novaCotacao;

                });

        } catch (\Exception $e) {
            Log::error("Erro ao gerar cotação de renovação da Apólice #{$apolice->id}: ".$e->getMessage());
            return null;
        }
    }

    private function criarCotacaiRenovacao(Apolice $apolice): Cotacao
    {
        $dadosEspecificos = $apolice->dados_bem_assegurado ?? [];
        $dadosEspecificos['apolice_origem_id_temporario'] = $apolice->id;
        $dadosEspecificos['inicio_vigencia_renovacao'] = $apolice->data_fim;

        $novaCotacao = Cotacao::create([
            'segurado_id' => $apolice->segurado_id,
            'user_id' => $apolice->user_id, // Corretor responsável
            'filial_id' => $apolice->filial_id,
            'produto_id' => $apolice->cotacao->produto_id,
            'cobertura_selecionada' => $apolice->snapshot['coberturas'] ?? [],
            'dados_especificos' => $dadosEspecificos,
            'status' => 'Em Elaboração',
            'validade' => Carbon::now()->addDays(30),
            'valor_total' => $apolice->valor_total,
        ]);

        return $novaCotacao;
    }

    private function enviarEmail(Apolice $apolice, Cotacao $cotacao)
    {
        $atrasoEmSegundos = rand(5, 15);
                // delay
                RenovacaoEmailJob::dispatch($apolice, $cotacao)->delay(now()->addSeconds($atrasoEmSegundos));

                Log::info("Nova COTAÇÃO de renovação (#{$cotacao->id}) criada em estado 'Em elaboração' a partir da Apólice #{$apolice->numero_apolice}");
    }
}
