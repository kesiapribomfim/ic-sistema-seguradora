<?php

namespace App\Filament\Resources\SinistroResource\Pages;

use App\Filament\Resources\SinistroResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSinistro extends ViewRecord
{
    protected static string $resource = SinistroResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        activity()
            ->causedBy(auth()->user()) 
            ->performedOn($this->record) 
            ->event('view')
            ->log('Visualizou os dados do Sinistro');
    }
}
