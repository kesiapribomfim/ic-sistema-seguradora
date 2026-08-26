<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Segurado;
use App\Models\Apolice;
use App\Models\Sinistro;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'Gestor de Filial']);
    }
    protected function getStats(): array
    {
        $user = auth()->user();

        $seguradosQuery = Segurado::query();
        $apolicesQuery = Apolice::where('status', 'Vigente');
        $sinistrosQuery = Sinistro::where('status', 'Em análise');

        // Se NÃO for Administrador Geral, aplicamos o filtro da filial
        if (!$user->hasRole('Administrador Geral')) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            
            // A apólice tem a coluna filial_id direto na tabela
            $apolicesQuery->whereIn('filial_id', $filiaisIds);
            
            // O Sinistro acessa a filial através da Apólice (A Correção!)
            $sinistrosQuery->whereHas('apolice', function ($query) use ($filiaisIds) {
                $query->whereIn('filial_id', $filiaisIds);
            });
        }

        return [
            Stat::make('Total de Segurados', $seguradosQuery->count())
                ->description('Clientes cadastrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Apólices Vigentes', $apolicesQuery->count())
                ->description('Contratos ativos')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary'),

            Stat::make('Sinistros em Análise', $sinistrosQuery->count())
                ->description('Aguardando retorno')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}