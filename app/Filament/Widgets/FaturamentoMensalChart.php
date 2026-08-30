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
        return Auth::user()->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial']);
    }

    protected function getData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isGlobal = $user->hasAnyRole(['super_admin', 'Administrador Geral']);
        $anoAtual = Carbon::now()->year;

        // 1. Busca as apólices do ano
        $query = Apolice::whereNotIn('status', ['Cancelada', 'Em Elaboração'])
            ->whereYear('data_emissao', $anoAtual);

        // 2. Aplica o filtro da filial se for Gestor
        if (!$isGlobal) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            $query->whereIn('filial_id', $filiaisIds);
        }

        // 3. Traz apenas os campos necessários para economizar RAM
        $apolices = $query->get(['data_emissao', 'valor_total']);

        // 4. Cria um array zerado para os 12 meses
        $dadosMensais = array_fill(1, 12, 0);

        // 5. Soma o valor total dentro do mês correspondente
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
                    'data' => array_values($dadosMensais), // Pega só os valores na ordem dos meses
                    'backgroundColor' => '#3b82f6', // Azul Tailwind
                    'borderColor' => '#3b82f6',
                    'fill' => 'start', // Dá um efeito bonito preenchendo abaixo da linha
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
