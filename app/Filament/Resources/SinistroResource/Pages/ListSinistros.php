<?php

namespace App\Filament\Resources\SinistroResource\Pages;

use App\Filament\Resources\SinistroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab; // <-- O IMPORT CORRETO DA ABA!
use Illuminate\Database\Eloquent\Builder;
use App\Models\Sinistro;

class ListSinistros extends ListRecords
{
    protected static string $resource = SinistroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Define as abas no topo da tabela de listagem
     */
    public function getTabs(): array
    {
        $user = auth()->user();

        if ($user->hasRole('Analista de Sinistros')) {

            return [
                'todos' => Tab::make('Todos os Sinistros'),
                    
                'meus_sinistros' => Tab::make('Meus Sinistros')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('analista_id', auth()->id())),
                
                'fila_espera' => Tab::make('Fila de Espera')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('analista_id')->where('status', 'Aberto'))
                    ->badge(Sinistro::whereNull('analista_id')->where('status', 'Aberto')->count()),
            ];
        }
        
        return [];
    }
}