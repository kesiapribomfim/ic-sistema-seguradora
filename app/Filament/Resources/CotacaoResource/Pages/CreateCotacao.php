<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCotacao extends CreateRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $coberturas = $data['coberturas_selecionadas'] ?? [];
        
        $somaLmi = collect($coberturas)->sum(fn($c) => (float) ($c['limite_maximo'] ?? 0));

        if ($somaLmi > 500000) {
            $data['status'] = 'Em Análise (Subscritor)';
        } else {
            $data['status'] = 'Enviada ao cliente'; 
        }

        return $data;
    }
    
}
