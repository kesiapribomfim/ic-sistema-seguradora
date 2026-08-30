<?php

namespace App\Services;

use App\Models\Segurado;
use App\Models\Apolice;
use App\Models\Sinistro;
use Carbon\Carbon; 

class EstatisticaDashboardService
{
    public function obterEstatisticas(array $filiaisIds = [], bool $isGlobal = false): array
    {
        $anoAtual = Carbon::now()->year;

        $seguradosQuery = Segurado::query();
        $apolicesVigentesQuery = Apolice::where('status', 'Vigente');
        $sinistrosAnaliseQuery = Sinistro::where('status', 'Em análise');

        $faturamentoQuery = Apolice::whereNotIn('status', ['Cancelada', 'Em Elaboração'])
            ->whereYear('data_emissao', $anoAtual);

        $custoSinistrosQuery = Sinistro::whereIn('status', ['Aprovado', 'Pago', 'Encerrado'])
            ->whereYear('data_hora_ocorrencia', $anoAtual);

        if (!$isGlobal && !empty($filiaisIds)) {
            $seguradosQuery->where(function ($q) use ($filiaisIds) {
                $q->whereHas('corretor.filiais', fn($q2) => $q2->whereIn('filiais.id', $filiaisIds))
                    ->orWhereHas('apolices', fn($q3) => $q3->whereIn('filial_id', $filiaisIds))
                    ->orWhereHas('cotacoes', fn($q4) => $q4->whereIn('filial_id', $filiaisIds))
                    ->orWhereHas('user.filiais', fn($q5) => $q5->whereIn('filiais.id', $filiaisIds));
            });

            $apolicesVigentesQuery->whereIn('filial_id', $filiaisIds);
            $faturamentoQuery->whereIn('filial_id', $filiaisIds);

            $sinistrosAnaliseQuery->whereHas('apolice', fn($q) => $q->whereIn('filial_id', $filiaisIds));
            $custoSinistrosQuery->whereHas('apolice', fn($q) => $q->whereIn('filial_id', $filiaisIds));
        }

        $faturamentoTotal = $faturamentoQuery->sum('valor_total');
        $custoTotalSinistros = $custoSinistrosQuery->sum('valor_indenizacao');

        $sinistralidade = $faturamentoTotal > 0 ? ($custoTotalSinistros / $faturamentoTotal) * 100 : 0;

        return [
            'total_segurados'       => $seguradosQuery->count(),
            'apolices_vigentes'     => $apolicesVigentesQuery->count(),
            'sinistros_analise'     => $sinistrosAnaliseQuery->count(),
            'faturamento_total'     => $faturamentoTotal,
            'custo_total_sinistros' => $custoTotalSinistros,
            'sinistralidade'        => $sinistralidade,
        ];
    }
}
