<?php

namespace App\Filament\Widgets;

use App\Models\Apolice;
use App\Models\Cotacao;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CorretorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Corretor');
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make('Cotações Pendentes', Cotacao::where('user_id', $userId)->whereIn('status', ['Em Elaboração', 'Enviada ao Cliente'])->count())
                ->description('Aguardando fechamento')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Minhas Apólices Vigentes', Apolice::where('user_id', $userId)->where('status', 'Vigente')->count())
                ->description('Carteira ativa')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('Prêmios Vendidos (Mês)', 'R$ ' . number_format(Apolice::where('user_id', $userId)->whereMonth('data_emissao', now()->month)->sum('valor_total'), 2, ',', '.'))
                ->description('Volume de vendas no mês atual')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }
}