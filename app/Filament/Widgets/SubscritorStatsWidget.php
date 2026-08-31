<?php

namespace App\Filament\Widgets;

use App\Models\Cotacao;
use App\Models\Produto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscritorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Subscritor');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Fila de Subscrição', Cotacao::where('status', 'Aguardando Subscrição')->count())
                ->description('Cotações acima da alçada comercial')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Produtos Comerciais Ativos', Produto::where('status', true)->count())
                ->description('Catálogo de vendas liberado')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),
                
            Stat::make('Cotações Recusadas (Mês)', Cotacao::where('status', 'Recusada')->whereMonth('updated_at', now()->month)->count())
                ->description('Riscos não aceitos')
                ->color('gray'),
        ];
    }
}