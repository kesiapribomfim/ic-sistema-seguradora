<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCotacaos extends ListRecords
{
    protected static string $resource = CotacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
