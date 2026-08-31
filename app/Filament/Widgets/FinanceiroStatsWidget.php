<?php

namespace App\Filament\Widgets;

use App\Models\Sinistro;
use App\Models\Pagamento; // AJUSTE AQUI SE O SEU MODEL TIVER OUTRO NOME
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceiroStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Financeiro');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        
        $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();

        $sinistrosQuery = Sinistro::whereHas('apolice', function ($query) use ($filiaisIds) {
            $query->whereIn('filial_id', $filiaisIds);
        });

        $pagamentosQuery = Pagamento::whereHas('apolice', function ($query) use ($filiaisIds) {
            $query->whereIn('filial_id', $filiaisIds);
        });

        return [
            Stat::make('Indenizações a Pagar', 'R$ ' . number_format((clone $sinistrosQuery)->where('status', 'Aprovado')->sum('valor_indenizacao'), 2, ',', '.'))
                ->description('Sinistros regulados pendentes de repasse')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Parcelas Atrasadas', (clone $pagamentosQuery)->where('status', 'Vencida')->count())
                ->description('Inadimplência ativa')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Contas a Receber', 'R$ ' . number_format((clone $pagamentosQuery)->where('status', 'Aberta')->sum('valor'), 2, ',', '.'))
                ->description('Previsão de entrada de parcelas')
                ->color('info'),
        ];
    }
}