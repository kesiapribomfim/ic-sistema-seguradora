<?php

namespace App\Filament\Resources\ProdutoResource\Pages;

use App\Filament\Resources\ProdutoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Override;

class ViewProduto extends ViewRecord
{
    protected static string $resource = ProdutoResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('fatura_parcela')
            ->label('Emitir Fatura')
            ->color('danger')
            //lógica para emitir pdf
            // ->action()
        ];
    }
}
