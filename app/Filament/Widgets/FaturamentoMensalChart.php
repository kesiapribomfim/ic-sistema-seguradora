<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Apolice;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FaturamentoMensalChart extends ChartWidget
{
    protected static ?string $heading = 'Evolução do Faturamento (Ano Atual)';
    protected static ?int $sort = 2; // Ficará logo abaixo dos cards

    public static function canView(): bool
    {
        // 1. Adicionado o Financeiro aqui!
        return Auth::user()->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial', 'Financeiro']);
    }

    protected function getData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $isGlobal = $user->hasAnyRole(['super_admin', 'Administrador Geral']);
        $anoAtual = Carbon::now()->year;

        $query = Apolice::whereNotIn('status', ['Cancelada', 'Em Elaboração'])
            ->whereYear('data_emissao', $anoAtual);

        if (!$isGlobal) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            $query->whereIn('filial_id', $filiaisIds);
        }

        $apolices = $query->get(['data_emissao', 'valor_total']);

        $dadosMensais = array_fill(1, 12, 0);

        foreach ($apolices as $apolice) {
            if ($apolice->data_emissao) {
                $mes = $apolice->data_emissao->month;
                $dadosMensais[$mes] += (float) $apolice->valor_total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Prêmios Arrecadados (R$)',
                    'data' => array_values($dadosMensais), 
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                    'fill' => 'start',
                ],
            ],
            'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Gráfico de linha
    }
}