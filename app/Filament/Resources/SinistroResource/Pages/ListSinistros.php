<?php

namespace App\Filament\Resources\SinistroResource\Pages;

use App\Filament\Resources\SinistroResource;
use App\Models\Sinistro;
use App\Models\User;
use Filament\Actions; // <-- O IMPORT CORRETO DA ABA!
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
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
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasRole('Analista de Sinistros')) {

            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();

            $quantidadeFilaEspera = Sinistro::whereNull('analista_id')
                ->where('status', 'Aberto')
                ->whereHas('apolice', function ($q) use ($filiaisIds) {
                    $q->whereIn('filial_id', $filiaisIds);
                })->count();

            return [
                'todos' => Tab::make('Todos os Sinistros'),

                'meus_sinistros' => Tab::make('Meus Sinistros')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('analista_id', $user->id)),

                'fila_espera' => Tab::make('Fila de Espera')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('analista_id')->where('status', 'Aberto'))
                    ->badge($quantidadeFilaEspera),
            ];
        }

        return [];
    }
}
