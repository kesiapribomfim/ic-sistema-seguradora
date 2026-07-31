<?php
namespace App\Filament\Resources\PagamentoResource\Pages;

use App\Filament\Resources\PagamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

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
        return [
            'todas' => Tab::make('Todas as Movimentações')
                ->icon('heroicon-o-list-bullet'),
                
            'receitas' => Tab::make('Receitas (Prêmios)')
                ->icon('heroicon-o-arrow-trending-up')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_movimentacao', 'Recebimento')),
                
            'despesas' => Tab::make('Despesas (Sinistros)')
                ->icon('heroicon-o-arrow-trending-down')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_movimentacao', 'Pagamento Indenização')),
                
            'inadimplencia' => Tab::make('Inadimplência')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_movimentacao', 'Recebimento')->where('status', 'Vencida')),
        ];
    }
}