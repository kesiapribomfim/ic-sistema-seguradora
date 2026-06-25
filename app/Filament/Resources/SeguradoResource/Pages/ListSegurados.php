<?php

namespace App\Filament\Resources\SeguradoResource\Pages;

use App\Filament\Resources\SeguradoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSegurados extends ListRecords
{
    protected static string $resource = SeguradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
