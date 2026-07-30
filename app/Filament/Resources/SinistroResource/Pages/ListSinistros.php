<?php

namespace App\Filament\Resources\SinistroResource\Pages;

use App\Filament\Resources\SinistroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSinistros extends ListRecords
{
    protected static string $resource = SinistroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
