<?php

namespace App\Services;

use App\Models\Apolice;
use App\Models\Segurado;
use App\Models\Sinistro;
use Carbon\Carbon;

class EstatisticaDashboardService
{
    public function obterEstatisticas(array $filiaisIds = [], bool $isGlobal = false): array
    {
        $anoAtual = Carbon::now()->year;
        $aplicarFiltro = !$isGlobal && !empty($filiaisIds);

        $totalSegurados = Segurado::where('status', true)
            ->when($aplicarFiltro, fn ($q) => $q->porFiliais($filiaisIds))
            ->count();
        
        $apolicesVigentes = Apolice::where('status', 'Vigente')
            ->when($aplicarFiltro, fn($q) => $q->whereIn('filial_id', $filiaisIds))
            ->count();

        $sinistrosAnalise = Sinistro::where('status', 'Em análise')
            ->when($aplicarFiltro, fn ($q) => $q->whereHas('apolice', fn ($a) => $a->whereIn('filial_id', $filiaisIds)))
            ->count();

        $faturamentoTotal = (float) Apolice::whereNotIn('status', ['Cancelada', 'Em Elaboração'])
            ->whereYear('data_emissao', $anoAtual)
            ->when($aplicarFiltro, fn($q) => $q->whereIn('filial_id', $filiaisIds))
            ->sum('valor_total');

        $custoTotalSinistros = (float) Sinistro::whereIn('status', ['Aprovado', 'Pago', 'Encerrado'])
            ->whereYear('data_hora_ocorrencia', $anoAtual)
            ->when($aplicarFiltro, fn($q) => $q->whereHas('apolice', fn ($a) => $a->whereIn('filial_id', $filiaisIds)))
            ->sum('valor_indenizacao');

        return [
            'total_segurados'       => $totalSegurados,
            'apolices_vigentes'     => $apolicesVigentes,
            'sinistros_analise'     => $sinistrosAnalise,
            'faturamento_total'     => $faturamentoTotal,
            'custo_total_sinistros' => $custoTotalSinistros,
            'sinistralidade'        => $this->calcularSinistralidade($faturamentoTotal, $custoTotalSinistros),
        ];
    }

    private function calcularSinistralidade(float $faturamento, float $custo): float
    {
        if ($faturamento <= 0.0) {
            return 0.0;
        }
        return ($custo / $faturamento) * 100;
    }
}
