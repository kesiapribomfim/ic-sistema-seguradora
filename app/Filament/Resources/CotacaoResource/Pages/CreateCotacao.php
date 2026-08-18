<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCotacao extends CreateRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garante que toda cotação nasça corretamente no primeiro estágio do funil
        $data['status'] = 'Em Elaboração';

        return $data;
    }
}