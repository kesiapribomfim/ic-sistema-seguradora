<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCotacao extends CreateRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Enviar Proposta')
            ->icon('heroicon-o-paper-airplane')
            ->color('info');
    }

    protected function getCreateApolice(): \Filament\Actions\Action
    {
        return parent::getCreateApolice()
            ->label('Criar cotação')
            //->icon ('heroic-o-papper-arplane')
            ->color ('sucess');
    }

    // 2. Intercepta os dados antes do INSERT no banco para definir o status correto
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $coberturas = $data['coberturas_selecionadas'] ?? [];
        
        // Soma todos os limites (LMI) do Repeater de coberturas
        $somaLmi = collect($coberturas)->sum(fn($c) => (float) ($c['limite_maximo'] ?? 0));

        // Regra de Alçada: Se passar de 500 mil, trava no Subscritor. Senão, libera.
        if ($somaLmi > 500000) {
            $data['status'] = 'Em Análise (Subscritor)';
        } else {
            $data['status'] = 'Enviada ao cliente'; 
        }

        return $data;
    }
    
}
