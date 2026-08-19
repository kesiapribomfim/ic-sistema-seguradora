<?php

namespace Database\Seeders;

use App\Models\Apolice;
use App\Models\Cotacao;
use Carbon\Carbon; // Não esqueça de importar o Carbon!
use Illuminate\Database\Seeder;

class ApoliceSeeder extends Seeder
{
    public function run(): void
    {
        $cotacoesAceitas = Cotacao::where('status', 'Aceita')->get();

        $metade = (int) ($cotacoesAceitas->count() / 2);
        $cotacoesParaEmitir = $cotacoesAceitas->take($metade);

        $contador = 0;

        foreach ($cotacoesParaEmitir as $cotacao) {
            // Manipulação do tempo para testes da esteira de renovação
            if ($contador === 0) {
                // Vence em 30 dias exatos
                $dataFim = Carbon::now()->addDays(30);
                $dataInicio = Carbon::now()->subDays(335); // 1 ano para trás
            } elseif ($contador === 1) {
                // Vence em 15 dias exatos
                $dataFim = Carbon::now()->addDays(15);
                $dataInicio = Carbon::now()->subDays(350);
            } elseif ($contador === 2) {
                // Vence em 60 dias exatos
                $dataFim = Carbon::now()->addDays(60);
                $dataInicio = Carbon::now()->subDays(305);
            } elseif ($contador === 3) {
                // Já expirou ontem
                $dataFim = Carbon::now()->subDay();
                $dataInicio = Carbon::now()->subDays(366);
            } else {
                // Apólice padrão recém-emitida (1 ano normal)
                $dataFim = Carbon::now()->addYear();
                $dataInicio = Carbon::now();
            }

            Apolice::factory()->create([
                'cotacao_id'  => $cotacao->id,
                'segurado_id' => $cotacao->segurado_id,
                'filial_id'   => $cotacao->filial_id,
                'user_id'     => $cotacao->user_id,
                'valor_total' => $cotacao->valor_total,
                'data_inicio' => $dataInicio,
                'data_fim'    => $dataFim,
                'status'      => $dataFim->isPast() ? 'Expirada' : 'Vigente',
            ]);

            $contador++;
        }

        // Sua lógica original de renovação manual (simulando que já ocorreu)
        $apoliceAntiga = Apolice::first();

        if ($apoliceAntiga) {
            $cotacaoRenovacao = Cotacao::factory()->create([
                'segurado_id' => $apoliceAntiga->segurado_id,
                'status'      => 'Aceita',
            ]);

            Apolice::factory()->create([
                'cotacao_id'        => $cotacaoRenovacao->id,
                'segurado_id'       => $cotacaoRenovacao->segurado_id,
                'filial_id'         => $cotacaoRenovacao->filial_id,
                'user_id'           => $cotacaoRenovacao->user_id,
                'valor_total'       => $cotacaoRenovacao->valor_total,
                'status'            => 'Vigente',
                'apolice_origem_id' => $apoliceAntiga->id,
            ]);

            $apoliceAntiga->update(['status' => 'Renovada']);
        }
    }
}