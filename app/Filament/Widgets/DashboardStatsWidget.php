<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Services\EstatisticaDashboardService;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial']);
    }

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $isGlobal = $user->hasAnyRole(['super_admin', 'Administrador Geral']);
        $filiaisIds = $isGlobal ? [] : $user->filiais()->pluck('filiais.id')->toArray();

        $estatisticasService = app(EstatisticaDashboardService::class);
        $dados = $estatisticasService->obterEstatisticas($filiaisIds, $isGlobal);

        $corSinistralidade = $dados['sinistralidade'] <= 70 ? 'success' : ($dados['sinistralidade'] <= 90 ? 'warning' : 'danger');

        return [
            Stat::make('Total de Segurados', $dados['total_segurados'])
                ->description('Segurados cadastrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Apólices Vigentes', $dados['apolices_vigentes'])
                ->description('Contratos ativos')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary'),

            Stat::make('Faturamento (Prêmios)', 'R$ ' . number_format($dados['faturamento_total'], 2, ',', '.'))
                ->description('Apólices emitidas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Índice de Sinistralidade', number_format($dados['sinistralidade'], 1, ',', '.') . '%')
                ->description($dados['custo_total_sinistros'] > 0 ? 'R$ ' . number_format($dados['custo_total_sinistros'], 2, ',', '.') . ' em indenizações' : 'Operação saudável')
                ->descriptionIcon($dados['sinistralidade'] > 70 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($corSinistralidade),

            Stat::make('Sinistros em Análise', $dados['sinistros_analise'])
                ->description('Aguardando auditoria')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}
