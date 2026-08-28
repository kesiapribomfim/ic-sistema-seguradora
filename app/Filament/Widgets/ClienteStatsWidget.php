<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Apolice;
use App\Models\Pagamento;

class ClienteStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        // Só aparece se o usuário tiver o perfil de Cliente
        return auth()->user()->hasRole('Cliente');
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make('Minhas Apólices', Apolice::whereHas('segurado', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('status', 'Vigente')
                ->count())
                ->description('Contratos ativos')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('Faturas em Aberto', Pagamento::whereHas('apolice.segurado', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('status', 'Aberta')
                ->count())
                ->description('Aguardando pagamento')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}