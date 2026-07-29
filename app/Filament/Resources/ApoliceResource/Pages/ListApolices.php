<?php

namespace App\Filament\Resources\ApoliceResource\Pages;

use App\Filament\Resources\ApoliceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApolices extends ListRecords
{
    protected static string $resource = ApoliceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
