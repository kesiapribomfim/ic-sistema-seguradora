<?php

namespace App\Filament\Widgets;

use App\Models\Sinistro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalistaStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Analista de Sinistros');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        
        $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();

        $sinistrosQuery = Sinistro::whereHas('apolice', function ($query) use ($filiaisIds) {
            $query->whereIn('filial_id', $filiaisIds);
        });

        return [
            Stat::make('Meus Sinistros', (clone $sinistrosQuery)->where('analista_id', $user->id)->where('status', 'Em análise')->count())
                ->description('Em análise')
                ->descriptionIcon('heroicon-m-document-magnifying-glass')
                ->color('info'),

            Stat::make('Fila Geral de Abertos', (clone $sinistrosQuery)->where('status', 'Aberto')->count())
                ->description('Aguardando triagem inicial')
                ->color('warning'),

            Stat::make('Aguardando Gestor', (clone $sinistrosQuery)->where('status', 'Aguardando Gestor')->count())
                ->description('Bloqueados por alçada')
                ->color('danger'),
        ];
    }
}