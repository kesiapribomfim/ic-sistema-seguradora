<?php

namespace App\Filament\Resources\SinistroResource\Pages;

use App\Filament\Resources\SinistroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab; // <-- O IMPORT CORRETO DA ABA!
use Illuminate\Database\Eloquent\Builder;
use App\Models\Sinistro;
use Illuminate\Support\Facades\Auth;

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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('Analista de Sinistros')) {
            
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();

            $quantidadeFilaEspera = \App\Models\Sinistro::whereNull('analista_id')
                ->where('status', 'Aberto')
                ->whereHas('apolice', function ($q) use ($filiaisIds) {
                    $q->whereIn('filial_id', $filiaisIds);
                })->count();

            return [
                'todos' => \Filament\Resources\Components\Tab::make('Todos os Sinistros'),
                    
                'meus_sinistros' => \Filament\Resources\Components\Tab::make('Meus Sinistros')
                    ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('analista_id', $user->id)),
                
                'fila_espera' => \Filament\Resources\Components\Tab::make('Fila de Espera')
                    ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNull('analista_id')->where('status', 'Aberto'))
                    ->badge($quantidadeFilaEspera),
            ];
        }
        
        return [];
    }
}