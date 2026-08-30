<?php
namespace App\Filament\Resources\PagamentoResource\Pages;

use App\Filament\Resources\PagamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPagamentos extends ListRecords
{
    protected static string $resource = PagamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->hasAnyRole('Financeiro', 'Gestor de Filial')) {
            return [
            'todas' => Tab::make('Todas as Movimentações')
                ->icon('heroicon-o-list-bullet'),
                
            'receitas' => Tab::make('Receitas (Prêmios)')
                ->icon('heroicon-o-arrow-trending-up')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('tipo_movimentacao', 'Recebimento')),
                
            'despesas' => Tab::make('Despesas (Sinistros)')
                ->icon('heroicon-o-arrow-trending-down')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('tipo_movimentacao', 'Pagamento Indenização')),
                
            'inadimplencia' => Tab::make('Inadimplência')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('tipo_movimentacao', 'Recebimento')
                    ->where('status', 'Vencida')),
            ];
        }

        if ($user->hasRole('Cliente')) {
            return [
                'todas' => Tab::make('Todas as Movimentações')
                    ->icon('heroicon-o-list-bullet'),

                'vencimento' => Tab::make('Vencimentos Próximos')
                    ->icon('heroicon-o-clock')
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->where('status', 'Aberta') 
                        ->whereBetween('data_vencimento', [
                            now()->startOfDay(), 
                            now()->addDays(30)->endOfDay()
                        ])),

                'indenizacoes' => Tab::make('Indenizações a Receber')
                    ->icon('heroicon-o-banknotes')
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->where('tipo_movimentacao', 'Pagamento Indenização')
                        ->where('status', 'Aberta')),

                'inadimplencia' => Tab::make('Inadimplência')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->where('tipo_movimentacao', 'Recebimento')
                        ->where('status', 'Vencida')),

            ];
        }

        return [];
        
    }
}