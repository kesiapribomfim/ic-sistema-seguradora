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

        if (!$user->hasRole('super_admin')) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            
            $seguradosQuery->where(function ($q) use ($filiaisIds) {
                $q->whereHas('corretor.filiais', function ($q2) use ($filiaisIds) {
                    $q2->whereIn('filiais.id', $filiaisIds);
                })
                ->orWhereHas('apolices', function ($q3) use ($filiaisIds) {
                    $q3->whereIn('filial_id', $filiaisIds);
                })
                ->orWhereHas('cotacoes', function ($q4) use ($filiaisIds) {
                    $q4->whereIn('filial_id', $filiaisIds);
                })
                ->orWhereHas('user.filiais', function ($q5) use ($filiaisIds) {
                    $q5->whereIn('filiais.id', $filiaisIds);
                });
            });

            $apolicesQuery->whereIn('filial_id', $filiaisIds);
            
            $sinistrosQuery->whereHas('apolice', function ($query) use ($filiaisIds) {
                $query->whereIn('filial_id', $filiaisIds);
            });
        }

        return [
            Stat::make('Total de Segurados', $seguradosQuery->count())
                ->description('Segurados cadastrados')
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